<?php namespace io\modelcontextprotocol\server;

use Throwable as Any;
use io\streams\Streams;
use web\Handler;
use web\auth\Authentication;
use web\filters\Invocation;
use web\handler\Call;
use web\session\Sessions;

/**
 * OAuth2 gateway
 *
 * @see   https://modelcontextprotocol.io/specification/2025-11-25/basic/authorization
 * @test  io.modelcontextprotocol.unittest.OAuth2GatewayTest
 */
class OAuth2Gateway {
  private $base, $clients, $tokens;

  /** Creates an MCP authentication handler based on OAuth2 */
  public function __construct(string $base, Clients $clients, Tokens $tokens) {
    $this->base= trim($base, '/');
    $this->clients= $clients;
    $this->tokens= $tokens;
  }

  /** URL-safe base64 encoding */
  private static function base64(string $input): string {
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
  }

  /**
   * Sends a 200 OK and the given value serialized as JSON
   *
   * @param  web.Response $response
   * @param  var $value
   * @return void
   */
  private function result($response, $value) {
    $response->answer(200);
    $response->send(json_encode($value), 'application/json');
  }

  /**
   * Sends a 302 Found and the given location 
   *
   * @param  web.Response $response
   * @param  string $location
   * @return void
   */
  private function redirect($response, $location) {
    $response->answer(302);
    $response->header('Location', $location);
  }

  /**
   * Sends a 400 Bad Request and the given error and description serialized as JSON
   *
   * @param  web.Response $response
   * @param  int $status
   * @param  string $error
   * @param  string $description
   * @return void
   */
  private function error($response, $status, $error, $description) {
    $response->answer($status);
    $response->send(json_encode(['error' => $error, 'error_description' => $description]), 'application/json');
  }

  /** @return string */
  public function continuation() { return "/{$this->base}/continuation"; }

  /** @return function(web.Request, web.Response) */
  public function metadata() {
    return function($request, $response) {
      $host= $request->uri()->base();
      return $this->result($response, [
        'issuer'                                => "{$host}",
        'authorization_endpoint'                => "{$host}/{$this->base}/authorize",
        'token_endpoint'                        => "{$host}/{$this->base}/token",
        'registration_endpoint'                 => "{$host}/{$this->base}/register",
        'response_types_supported'              => ['code'],
        'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
        'code_challenge_methods_supported'      => ['S256'],
        'token_endpoint_auth_methods_supported' => ['none']
      ]);
    };
  }

  /** @return function(web.Request, web.Response) */
  public function flow(Authentication $auth, Sessions $sessions) {
    return function($request, $response) use($auth, $sessions) {
      $path= $request->method().' '.$request->uri()->path();
      switch ($path) {
        case "POST /{$this->base}/register":
          $payload= json_decode(Streams::readAll($request->stream()), true);
          try {
            $client= $this->clients->register($payload);
            $response->trace('registered', $client);
            return $this->result($response, $client);
          } catch (Any $e) {
            return $this->error($response, 500, 'server_error', $e->getMessage());
          }
        
        case "GET /{$this->base}/authorize":
          if (!$this->clients->verify($request->param('client_id'), $request->param('redirect_uri'))) {
            return $this->error(
              $response,
              400,
              'invalid_client',
              'Cannot authorize client '.$request->param('client_id')
            );
          }

          // Create session and register flow
          $session= $sessions->create();
          $session->register('flow', [
            'client'    => $request->param('client_id'),
            'redirect'  => $request->param('redirect_uri'),
            'method'    => $request->param('code_challenge_method'),
            'challenge' => $request->param('code_challenge'),
          ]);
          $session->transmit($response);
          // Fall through

        case "GET /{$this->base}/continuation":
          return $auth->filter($request, $response, new Invocation(function($request, $response) use($sessions, &$session) {
            $response->trace('client', $request->param('client_id'));

            // Verify session exists and contains a valid flow
            $session??= $sessions->locate($request);
            if (!$session || !$session->value('flow')) {
              return $this->error($response, 400, 'invalid_request', 'Call authorize first');
            }

            // Register user in session
            $session->register('user', $request->value('user'));
            $session->transmit($response);

            // Then, redirect to the specified redirect_uri
            return $this->redirect($response, sprintf(
              '%s?code=%s&state=%s',
              $request->param('redirect_uri'),
              $session->id(),
              $request->param('state')
            ));
          }));

        case "POST /{$this->base}/token":
          $response->trace('client', $request->param('client_id'));

          if ('authorization_code' !== $request->param('grant_type')) {
            return $this->error($response, 'unsupported_grant_type', 'Grant type unsupported');
          } else if (empty($verifier= $request->param('code_verifier'))) {
            return $this->error($response, 'invalid_grant', 'Invalid authorization grant');
          }

          // Confirm that the code (:= session)
          // - Exists
          // - Was issued by your authorization server
          // - Has not expired
          // - Has not already been used (single-use)
          $session= $sessions->open($request->param('code'));
          if (null === $session) {
            $flow= ['method' => ':EXPIRED', 'client' => '', 'redirect' => '', 'challenge' => ''];
          } else {
            $flow= $session->value('flow') ?? ['method' => ':REUSED', 'client' => '', 'redirect' => '', 'challenge' => ''];
          }

          // - Is associated with the same client_id and the same redirect_uri
          // - Verifies according to PKCE methods S256 and plain; or is FALSE
          $c= hash_equals($flow['client'], $request->param('client_id'));
          $r= hash_equals($flow['redirect'], $request->param('redirect_uri'));
          if ('S256' === $flow['method']) {
            $v= hash_equals($flow['challenge'], self::base64(hash('sha256', $verifier, true)));
          } else if ('plain' === $flow['method']) {
            $v= hash_equals($flow['challenge'], $verifier);
          } else {
            $v= false;
          }

          // Do not give a precise error message to not give attackers any hint
          if (!$c || !$r || !$v) {
            return $this->error(
              $response,
              400,
              'invalid_grant',
              'Invalid, expired or already used authorization code, or PKCE verification failed'
            );
          }

          // Invalidate the flow, clients may retry the above step (RFC 6749 §4.1.2 and §4.1.3)
          $session->remove('flow');
          $token= $this->tokens->issue((string)$request->uri()->base(), $flow['client'], $session);
          $session->transmit($response);
          
          // Create token and return
          return $this->result($response, ['token_type' => 'Bearer', 'access_token' => $token]);

        default:
          return $this->error($response, 'invalid_request', 'Cannot handle requests to '.$path);
      }
    };
  }

  /** @return function(web.Request, web.Response) */
  public function authenticate($target) {
    $handler= $target instanceof Handler ? $target : new Call($target);
    return function($request, $response) use($handler) {
      $r= sscanf($request->header('Authorization') ?? '', 'Bearer %s', $token);
      if (1 === $r && ($user= $this->tokens->use($token))) {
        return $handler->handle($request->pass('user', $user), $response);
      }

      $response->answer(401);
      $response->header('WWW-Authenticate', 'Bearer');
    };
  }
}