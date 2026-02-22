class McpHost {
  #nextId = 1;
  #server = null;
  #pending = {};
  messages = console.warn;
  links = console.warn;

  constructor(frame, endpoint, name, version) {
    this.frame = frame;
    this.endpoint = endpoint;
    this.name = name;
    this.version = version;
  }

  async #send(payload) {
    return await fetch(this.endpoint, {
      method: 'POST',
      body: JSON.stringify({ jsonrpc: '2.0', id: this.#nextId++, ...payload }),
      headers: {
        'Content-Type' : 'application/json',
        'Accept': 'text/event-stream, application/json',
        ...this.authorization
      },
    });
  }

  async *linesIn(reader) {
    const decoder = new TextDecoder();

    let buffer = '';
    let n = 0;
    while (true) {
      const { done, value } = await reader.read();
      if (done) {
        if (buffer.length) yield buffer;
        return;
      }

      buffer += decoder.decode(value, { stream: true });
      while (-1 !== (n = buffer.indexOf('\n'))) {
        yield buffer.slice(0, n);
        buffer = buffer.slice(n + 1);
      }
    }
  }

  async #read(response) {
    const type = response.headers.get('Content-Type');

    if (type.startsWith('application/json')) {
      return response.json();
    } else if (type.startsWith('text/event-stream')) {
      for await (const line of this.linesIn(response.body.getReader())) {
        if (line.startsWith('data: ')) {
          return Promise.resolve(JSON.parse(line.slice(6)));
        }
      }
      return Promise.resolve(null);
    }

    throw new Error('Cannot handle mime type ' + type);
  }

  authorize(authorization) {
    this.authorization = authorization ? { 'Authorization' : authorization } : {};
  }

  async initialize(auth= undefined) {
    const cached = `mcp-auth:${this.endpoint}`;

    // Cache authorization per endpoint in session storage
    this.authorize(window.sessionStorage.getItem(cached) ?? await auth.authorize());

    // Perform initialization, refreshing authorization if necessary
    let initialize;
    do {
      initialize = await this.#send({ method: 'initialize', params: {
        protocolVersion: '2026-01-26',
        clientInfo: { name: this.name, version: this.version },
        capabilities: {},
      }});
      if (200 === initialize.status) {
        this.#server = await this.#read(initialize);
        return true;
      } else if (401 === initialize.status && auth) {
        const authorization = await auth.authorize();
        if (authorization) {
          window.sessionStorage.setItem(cached, authorization);
          this.authorize(authorization);
          continue;
        }
        throw new Error('Unauthorized');
      } else {
        throw new Error('Unexpected ' + initialize.status); 
      }
    } while (true);
  }

  /** Launches the given app */
  async launch(app, args = {}) {
    const call = await this.#read(await this.#send({ method: 'tools/call', params: {
      name: app.name,
      arguments: args,
    }}));
    const contents = await this.#read(await this.#send({ method: 'resources/read', params: {
      uri : app._meta.ui.resourceUri,
    }}));

    this.#pending[call.id] = { tool: app, input: args, result: call.result };

    // Render the app into the application iframe
    this.frame.dataset.call = call.id;

    //
    const inner = this.frame.contentDocument.createElement('iframe');
    inner.style = 'width: 100%; height: 100%; border: none;';
    inner.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms');
    // inner.src = '/static/mcp-proxy.html';
    inner.srcdoc = contents.result.contents[0].text;
    this.frame.contentDocument.documentElement.style = 'margin: 0; height: 100%';
    this.frame.contentDocument.body.style = 'margin: 0; height: 100%';
    this.frame.contentDocument.body.appendChild(inner);

    this.frame.contentWindow.onmessage = async e => {
      if ('2.0' !== e.data?.jsonrpc) return;

      switch (e.data.method) {
        case 'ui/initialize': 
          const styles = getComputedStyle(document.documentElement);
          const variables = {};
          for (let prop of styles) {
            if (prop.startsWith('--')) {
              variables[prop] = styles.getPropertyValue(prop);
            }
          }

          inner.contentWindow.postMessage({ jsonrpc: '2.0', id: e.data.id, result: {
            protocolVersion: '2026-01-26',
            hostInfo: { name: this.name, version: this.version },
            hostCapabilities: { /* TODO */ },
            hostContext: {
              toolInfo: {
                id: this.frame.dataset.call,
                tool: this.#pending[this.frame.dataset.call].tool,
              },
              platform: 'web',
              userAgent: navigator.userAgent,
              // TODO: locale
              // TODO: timeZone
              deviceCapabilities: { hover: true, touch: true },
              displayMode: 'inline',
              availableDisplayModes: ['inline'],
              safeAreaInsets: { top: 0, right: 0, bottom: 0, left: 0 },
              containerDimensions: { 
                maxWidth: this.frame.contentWindow.innerWidth,
                maxHeight: this.frame.contentWindow.innerHeight,
              },
              theme: 'light',
              styles: { variables },
            },
          }});
          break;

        case 'ui/notifications/initialized':
          inner.contentWindow.postMessage({
            jsonrpc: '2.0',
            method: 'ui/notifications/tool-input',
            params: this.#pending[this.frame.dataset.call].input
          });
          inner.contentWindow.postMessage({
            jsonrpc: '2.0',
            method: 'ui/notifications/tool-result',
            params: this.#pending[this.frame.dataset.call].result
          });
          delete this.#pending[this.frame.dataset.call];
          break;

        case 'ui/notifications/size-changed':
          console.warn('Not yet implemented', e.data);
          break;

        case 'ui/open-link':
          this.links(e.data.params.url, e.data.id);
          inner.contentWindow.postMessage({ jsonrpc: '2.0', id: e.data.id, result: {} });
          break;

        case 'ui/message':
          this.messages(e.data.params.content, e.data.id);
          inner.contentWindow.postMessage({ jsonrpc: '2.0', id: e.data.id, result: {} });
          break;

        default: // Proxy MCP 
          inner.contentWindow.postMessage(await this.#read(await this.#send(e.data)));
          break;
      }
    };
  }

  /** Returns all MCP apps for the given server */
  async *apps() {
    const tools = await this.#read(await this.#send({ method: 'tools/list' }));

    for (const tool of tools.result.tools) {
      if (tool._meta && 'ui' in tool._meta) {
        yield tool;
      }
    }
  }
}