class McpOAuth {
  #UNRESERVED = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';

  constructor() {
    const cached = window.sessionStorage.getItem('mcp-oauth');
    this.client = cached ? JSON.parse(cached) : {
      client_name: 'XP/MCP Host',
      redirect_uris: [window.location.href],
      grant_types: ['authorization_code'],
      response_types: ['code'],
      token_endpoint_auth_method: 'none',
    };
  }

  /** Generates random code verifier */
  #verifier() {
    const random = new Uint8Array(64);
    crypto.getRandomValues(random);
    
    let verifier = '';
    for (let i = 0; i < 64; i++) {
      verifier += this.#UNRESERVED[random[i] % 66];
    }
    return verifier;
  }

  /** URL-safe base64 encoded challenge for verifier */
  async #challenge(verifier) {
    const sha = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier));
    const b64 = btoa(new Uint8Array(sha).reduce((data, byte) => data + String.fromCharCode(byte), ''));
    return b64.replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '');
  }

  /** Performs authorization */
  async authorize() {
    const url = new URL(window.location.href);
    const code = url.searchParams.get('code');

    if (null === code) {
      let response;

      // Check for OAuth flow
      response = await fetch('/.well-known/oauth-protected-resource');
      if (!response.ok) {
        throw new Error('Unauthorized, and no OAuth flow possible');
      }

      const resource = await response.json();
      response = await fetch(resource['authorization_servers'][0] + '/.well-known/oauth-authorization-server');
      if (!response.ok) {
        throw new Error('Unauthorized, and no OAuth meta data from ' + response.url);
      }

      // Register OAuth client if necessary
      const meta = await response.json();
      if ('client_id' in this.client) {
        console.warn('Reusing OAuth client', this.client);
      } else {
        response = await fetch(meta['registration_endpoint'], {
          method: 'POST',
          body: JSON.stringify(this.client),
          headers: { 'Content-Type': 'application/json' },
        });
        if (!response.ok) {
          throw new Error('Client registration failed from ' + response.url);
        }

        this.client = await response.json();
        console.warn('Registered OAuth client', this.client);
        window.sessionStorage.setItem('mcp-oauth', JSON.stringify(this.client));
      }

      // Start OAuth flow by redirecting to authorization endpoint
      const verifier = this.#verifier();
      const challenge = await this.#challenge(verifier);
      const state = crypto.randomUUID();

      window.sessionStorage.setItem(`mcp-${state}`, JSON.stringify({ verifier, meta }));
      window.location.replace(`${meta['authorization_endpoint']}?client_id=${this.client['client_id']}&response_type=code&state=${state}&code_challenge=${challenge}&code_challenge_method=S256&redirect_uri=${encodeURIComponent(this.client['redirect_uris'][0])}`);

      return Promise.resolve(null);
    } else {
      const state = url.searchParams.get('state');
      const verify = JSON.parse(window.sessionStorage.getItem(`mcp-${state}`));
      window.sessionStorage.removeItem(`mcp-${state}`);

      // Exchange code for access token      
      const response = await fetch(verify.meta['token_endpoint'], {
        method: 'POST',
        body: new URLSearchParams({
          code,
          state,
          grant_type: 'authorization_code',
          client_id: this.client['client_id'],
          redirect_uri: this.client['redirect_uris'][0],
          code_verifier: verify.verifier,
        }),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      });
      if (!response.ok) {
        throw new Error('Token exchange failed for ' + response.url);
      }

      const token = await response.json();

      // Remove query string and state, then return authorization
      url.search = '';
      window.history.replaceState({}, document.title, url.href);

      return Promise.resolve(`${token['token_type']} ${token['access_token']}`);
    }
  }
}