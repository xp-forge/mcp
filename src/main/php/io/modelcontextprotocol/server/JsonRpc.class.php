<?php namespace io\modelcontextprotocol\server;

use Throwable as Any;
use lang\{Throwable, FormatException, MethodNotImplementedException};
use text\json\{StreamInput, StreamOutput};
use util\log\Traceable;
use web\Handler;

/**
 * JSON RPC implementation
 *
 * @see   https://json-rpc.dev/docs/reference/error-codes
 * @test  io.modelcontextprotocol.unittest.JsonRpcTest
 */
class JsonRpc implements Handler, Traceable {
  const JSON             = 'application/json';
  const INVALID_REQUEST  = -32600;
  const METHOD_NOT_FOUND = -32601;
  const INVALID_PARAMS   = -32602;
  const INTERNAL_ERROR   = -32603;
  const PARSE_ERROR      = -32700;

  private $routes= [];
  private $cat= null;

  /** @param [:function(mixed): mixed] $routes */
  public function __construct($routes) {
    foreach ($routes as $path => $route) {
      $this->routes['#^'.$path.'#']= $route;
    }
  }

  /** @param util.log.LogCategory $cat */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  /**
   * Receive JSON RPC payload from the request
   *
   * @param  web.Request $request
   * @return var
   * @throws lang.FormatException
   */
  public function receive($request) {
    $header= $request->header('Content-Type') ?? '';
    if (0 !== strncmp($header, self::JSON, strlen(self::JSON))) {
      throw new FormatException('Expected '.self::JSON.', have '.$header);
    }

    $input= new StreamInput($request->stream());
    try {
      return $input->read();
    } finally {
      $input->close();
    }
  }

  /**
   * Sends an answer via JSON RPC to the response
   *
   * @param  web.Response $response
   * @param  var $answer
   * @return void
   */
  public function send($response, $answer) {
    $payload= ['jsonrpc' => '2.0'] + $answer;
    $response->header('Content-Type', self::JSON);

    $output= new StreamOutput($response->stream());
    try {
      $output->write($payload);
    } finally {
      $output->close();
    }
  }

  /** Handles requests */
  public function handle($request, $response) {
    try {
      $payload= $this->receive($request);
      $this->cat && $this->cat->debug('>>>', $payload);

      foreach ($this->routes as $pattern => $route) {
        if (preg_match($pattern, $payload['method'], $matches)) {
          $result= $route($payload, $matches);
          $this->cat && $this->cat->debug('<<<', $result);

          if ($result instanceof Response) {
            $response->answer($result->status);
            foreach ($result->headers as $name => $value) {
              $response->header($name, $value);
            }
            if (null === $result->value) {
              $response->flush();
            } else {
              $this->send($response, ['id' => $payload['id'], 'result' => $result->value]);
            }
          } else {
            $this->send($response, ['id' => $payload['id'], 'result' => $result]);
          }
          return;
        }
      }

      $this->cat && $this->cat->warn('<<<', 'Unhandled', array_keys($this->routes));
      $response->answer(400);
      $this->send($response, ['id' => $payload['id'] ?? null, 'error' => [
        'code'    => self::METHOD_NOT_FOUND,
        'message' => 'Cannot handle '.$payload['method'],
      ]]);
    } catch (Throwable $t) {
      $this->cat && $this->cat->warn('<<<', $t);

      $response->answer(400);
      $this->send($response, ['id' => $payload['id'] ?? null, 'error' => [
        'code'    => self::INVALID_REQUEST,
        'message' => $t->getMessage(),
      ]]);
    }
  }
}