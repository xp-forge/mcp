<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{OAuth2Gateway, Clients, Tokens};
use lang\IllegalStateException;
use test\{Assert, Test, Values};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class OAuth2GatewayTest {
  const VALID_TOKEN= 'test-token';
  const USER= ['id' => 'test'];

  /** Clients fixture */
  private function clients() {
    return new class() extends Clients { };
  }

  /** Tokens fixture */
  private function tokens() {
    return new class() extends Tokens {
      public function use($token) {
        return OAuth2GatewayTest::VALID_TOKEN === $token ? OAuth2GatewayTest::USER : null;
      }
    };
  }

  /**
   * Invokes the handler function
   *
   * @param  function(web.Request, web.Response): var $handler
   * @param  [:var] $headers
   * @return web.Response
   */
  private function handle($handler, $headers= []) {
    $request= new Request(new TestInput('GET', '/', $headers));
    $response= new Response(new TestOutput());

    foreach ($handler($request, $response) ?? [] as $_) { }
    return $response;
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

  #[Test]
  public function unauthenticated() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->authenticate(function($request, $response) {
      throw new IllegalStateException('Unreachable');
    });
    $response= $this->handle($handler);

    Assert::equals(401, $response->status());
    Assert::equals(['WWW-Authenticate' => 'Bearer'], $response->headers());
  }

  #[Test]
  public function authenticated() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->authenticate(function($request, $response) use(&$authenticated) {
      $authenticated= $request->value('user');
    });
    $response= $this->handle($handler, ['Authorization' => 'Bearer '.self::VALID_TOKEN]);

    Assert::equals(200, $response->status());
    Assert::equals(self::USER, $authenticated);
  }
}