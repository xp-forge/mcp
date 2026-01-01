<?php namespace io\modelcontextprotocol\server;

use web\session\{Sessions, ISession};

class UseSession extends Tokens {
  private $sessions;

  /** Reuses authentication session */
  public function __construct(Sessions $sessions) {
    $this->sessions= $sessions;
  }

  /** Issues a token */
  public function issue(string $issuer, string $audience, ISession $session): string {
    return $session->id();
  }

  /** Uses a token */
  public function use(string $token): ?array {
    if ($session= $this->sessions->open($token)) {
      try {
        return $session->value('user');
      } finally {
        $session->close();
      }
    }
    return null;
  }
}