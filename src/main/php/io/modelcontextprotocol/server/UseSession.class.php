<?php namespace io\modelcontextprotocol\server;

use web\session\{Sessions, ISession};

class UseSession extends Tokens {
  private $sessions;

  /** Reuses authentication session */
  public function __construct(Sessions $sessions) {
    $this->sessions= $sessions;
  }

  /**
   * Issues a token, returning the access token response
   *
   * @see https://www.oauth.com/oauth2-servers/access-tokens/access-token-response/
   */
  public function issue(string $issuer, array $flow, ISession $session): array {
    $token= ['access_token' => $session->id(), 'expires_in' => $sessions->expires() - time()];

    // Scopes are optional
    if (!empty($flow['scopes'])) {
      $session->register('scopes', $flow['scopes']);
      $token['scope']= implode(' ', $flow['scopes']);
    }

    return $token;
  }

  /** Uses a token */
  public function use(string $token): ?array {
    if ($session= $this->sessions->open($token)) {
      try {
        return ['user' => $session->value('user'), 'scopes' => $session->value('scopes')];
      } finally {
        $session->close();
      }
    }
    return null;
  }
}