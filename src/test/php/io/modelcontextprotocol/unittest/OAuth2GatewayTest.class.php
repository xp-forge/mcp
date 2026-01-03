<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{OAuth2Gateway, Clients, Tokens};
use lang\IllegalStateException;
use test\{Assert, Test, Values};
use util\URI;
use web\auth\Authentication;
use web\io\{TestInput, TestOutput};
use web\session\ForTesting;
use web\{Request, Response};

class OAuth2GatewayTest {
  const VALID_TOKEN= 'test-token';
  const VALID_CLIENT= 'test-client';
  const VALID_REDIRECT= 'http://localhost:35535/oauth/callback';
  const USER= ['id' => 'test'];

  /** Clients fixture */
  private function clients() {
    return new class() extends Clients {
      private $registered= [
        OAuth2GatewayTest::VALID_CLIENT => [
          'client_name'   => 'Test client',
          'scope'         => 'openid profile',
          'redirect_uris' => [OAuth2GatewayTest::VALID_REDIRECT],
        ],
      ];

      public function lookup($id) {
        return $this->registered[$id] ?? null;
      }
    };
  }

  /** Tokens fixture */
  private function tokens() {
    return new class() extends Tokens {

      public function issue($issuer, $audience, $session) {
        $session->destroy();
        return OAuth2GatewayTest::VALID_TOKEN;
      }

      public function use($token) {
        return OAuth2GatewayTest::VALID_TOKEN === $token ? OAuth2GatewayTest::USER : null;
      }
    };
  }

  /** Authentication delegation */
  private function auth() {
    return new class() extends Authentication {
      public function present($request) { return true; }
      public function filter($request, $response, $invokation) {
        return $invokation->proceed($request->pass('user', OAuth2GatewayTest::USER), $response);
      }
    };
  }

  /**
   * Invokes the handler function
   *
   * @param  function(web.Request, web.Response): var $handler
   * @param  var[] $request
   * @param  [:var] $headers
   * @return web.Response
   */
  private function handle($handler, $request, $headers= []) {
    [$method, $uri, $params]= $request;
    $request= new Request(new TestInput($method, $uri.'?'.http_build_query($params, PHP_QUERY_RFC3986), $headers));
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
  public function metadata() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $response= $this->handle($gateway->metadata(), ['GET', '/', []], ['Host' => 'test']);

    Assert::equals(200, $response->status());
    Assert::equals(
      [
        'issuer'                                => "http://test",
        'authorization_endpoint'                => "http://test/oauth/authorize",
        'token_endpoint'                        => "http://test/oauth/token",
        'registration_endpoint'                 => "http://test/oauth/register",
        'response_types_supported'              => ['code'],
        'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
        'code_challenge_methods_supported'      => ['S256'],
        'token_endpoint_auth_methods_supported' => ['none']
      ],
      json_decode($response->output()->body(), true)
    );
  }

  #[Test]
  public function authenticated_by_bearer_token() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->authenticate(function($request, $response) use(&$authenticated) {
      $authenticated= $request->value('user');
    });
    $response= $this->handle($handler, ['GET', '/', []], ['Authorization' => 'Bearer '.self::VALID_TOKEN]);

    Assert::equals(200, $response->status());
    Assert::equals(self::USER, $authenticated);
  }

  #[Test, Values([[[]], [['Authorization' => 'Bearer invalid_or_expired']]])]
  public function unauthenticated($headers) {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->authenticate(function($request, $response) {
      throw new IllegalStateException('Unreachable');
    });
    $response= $this->handle($handler, ['GET', '/', []], $headers);

    Assert::equals(401, $response->status());
    Assert::equals(['WWW-Authenticate' => 'Bearer'], $response->headers());
  }

  #[Test]
  public function authorize_redirection() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->flow($this->auth(), new ForTesting());
    $response= $this->handle($handler, ['GET', '/oauth/authorize', [
      'client_id'             => self::VALID_CLIENT,
      'redirect_uri'          => self::VALID_REDIRECT,
      'state'                 => 'test-state',
      'code_challenge'        => 'test-challenge',
      'code_challenge_method' => 'S256',
    ]]);
    $location= new URI($response->headers()['Location']);

    Assert::equals(302, $response->status());
    Assert::equals(self::VALID_REDIRECT, $location->base().$location->path());
    Assert::matches('/code=.+&state=test-state/', $location->query());
  }

  #[Test]
  public function cannot_authorize_invalid_redirect() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->flow($this->auth(), new ForTesting());
    $response= $this->handle($handler, ['GET', '/oauth/authorize', [
      'client_id'             => self::VALID_CLIENT,
      'redirect_uri'          => 'http://example.com',
      'state'                 => 'test-state',
      'code_challenge'        => 'test-challenge',
      'code_challenge_method' => 'S256',
    ]]);

    Assert::equals(400, $response->status());
    Assert::matches('/Cannot authorize client test-client/', $response->output()->body());
  }

  #[Test]
  public function cannot_authorize_unknown_client() {
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->flow($this->auth(), new ForTesting());
    $response= $this->handle($handler, ['GET', '/oauth/authorize', [
      'client_id'             => 'invalid-client',
      'redirect_uri'          => self::VALID_REDIRECT,
      'state'                 => 'test-state',
      'code_challenge'        => 'test-challenge',
      'code_challenge_method' => 'S256',
    ]]);

    Assert::equals(400, $response->status());
    Assert::matches('/Cannot authorize client invalid-client/', $response->output()->body());
  }

  #[Test]
  public function token_issued() {
    $challenge= 'test-challenge';
    $gateway= new OAuth2Gateway('/oauth', $this->clients(), $this->tokens());
    $handler= $gateway->flow($this->auth(), new ForTesting());

    // Step 1: Authorize
    $response= $this->handle($handler, ['GET', '/oauth/authorize', [
      'client_id'             => self::VALID_CLIENT,
      'redirect_uri'          => self::VALID_REDIRECT,
      'state'                 => 'test-state',
      'code_challenge'        => 'Xuq1l4Pllrvf6AJ2BfBwnQFQKBK7dnKAbolZ3zvWFlw', // base64(sha256(challeng))
      'code_challenge_method' => 'S256',
    ]]);
    $location= new URI($response->headers()['Location']);

    // Step 2: Fetch token
    $response= $this->handle($handler, ['POST', '/oauth/token', [
      'client_id'             => self::VALID_CLIENT,
      'redirect_uri'          => self::VALID_REDIRECT,
      'grant_type'            => 'authorization_code',
      'code'                  => $location->param('code'),
      'code_verifier'         => $challenge,
    ]]);

    Assert::equals(
      ['token_type' => 'Bearer', 'access_token' => self::VALID_TOKEN],
      json_decode($response->output()->body(), true)
    );
  }
}