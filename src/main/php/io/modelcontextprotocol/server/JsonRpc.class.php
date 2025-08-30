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
   * Sends a result via JSON RPC to the response
   *
   * @param  web.Response $response
   * @param  int|string $id
   * @param  var $result
   * @return void
   */
  private function send($response, $id, $result) {
    $response->answer(200);
    $response->header('Content-Type', self::JSON);

    $output= new StreamOutput($response->stream());
    try {
      $output->write(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    } finally {
      $output->close();
    }
  }

  /**
   * Sends an error via JSON RPC to the response
   *
   * @param  web.Response $response
   * @param  int|string $id
   * @param  var $result
   * @return void
   */
  private function error($response, $id, $code, $message) {
    $response->answer(400);
    $response->header('Content-Type', self::JSON);

    $output= new StreamOutput($response->stream());
    try {
      $output->write(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]]);
    } finally {
      $output->close();
    }
  }

  /** Handles requests */
  public function handle($request, $response) {
    $header= $request->header('Content-Type') ?? '';
    if (0 !== strncmp($header, self::JSON, strlen(self::JSON))) {
      throw new FormatException('Expected '.self::JSON.', have '.$header);
    }

    $input= new StreamInput($request->stream());
    try {
      $payload= $input->read();
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
              $this->send($response, $payload['id'], $result->value);
            }
          } else {
            $this->send($response, $payload['id'], $result);
          }
          return;
        }
      }

      $this->cat && $this->cat->warn('<<<', 'Unhandled', array_keys($this->routes));
      $this->error($response, $payload['id'], self::METHOD_NOT_FOUND, $payload['method']);
    } catch (FormatException $e) {
      $this->cat && $this->cat->warn('<<<', $e);
      $this->error($response, $payload['id'] ?? null, self::PARSE_ERROR, $e->getMessage());
    } catch (Any $e) {
      $this->cat && $this->cat->warn('<<<', Throwable::wrap($e));
      $this->error($response, $payload['id'] ?? null, self::INVALID_REQUEST, $e->getMessage());
    } finally {
      $input->close();
    }
  }
}