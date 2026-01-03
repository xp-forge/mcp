<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{OAuth2Gateway, Clients, Tokens};
use test\{Assert, Test, Values};

class OAuth2GatewayTest {

  /** Clients fixture */
  private function clients() {
    return new class() extends Clients { };
  }

  /** Tokens fixture */
  private function tokens() {
    return new class() extends Tokens { };
  }

  #[Test]
  public function can_create() {
    new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
  }

  #[Test, Values(['/oauth', '/oauth/v2'])]
  public function continuation($base) {
    Assert::equals(
      $base.'/continuation',
      (new OAuth2Gateway($base, $this->clients(), $this->tokens()))->continuation()
    );
  }
}