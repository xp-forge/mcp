<?php namespace io\modelcontextprotocol\server;

use Throwable;
use io\modelcontextprotocol\Capabilities;
use lang\FormatException;
use text\json\{Json, StreamInput, StreamOutput};
use util\log\Traceable;
use web\Handler;

class McpServer implements Handler, Traceable {
  const JSON= 'application/json';

  private $delegates;
  private $cat= null;

  public function __construct($arg, string $version= '2025-03-26') {
    $this->delegates= $arg instanceof Delegates ? $arg : new InstanceDelegate($arg);
    $this->version= $version;
    $this->capabilities= Capabilities::server();
  }

  /** @param util.log.LogCategory $cat */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  private function receive($request) {
    $header= $request->header('Content-Type');
    if (0 !== strncmp($header, self::JSON, strcspn($header, ';'))) {
      throw new FormatException('Expected '.self::JSON.', have '.$header);
    }

    $payload= Json::read(new StreamInput($request->stream()));
    $this->cat && $this->cat->debug('>>>', $payload);
    return $payload;
  }

  private function send($response, $answer) {
    $payload= ['jsonrpc' => '2.0'] + $answer;
    $response->header('Content-Type', self::JSON);

    Json::write($payload, new StreamOutput($response->stream()));
    $this->cat && $this->cat->debug('<<<', $payload);
  }

  public function handle($request, $response) {
    $route= $request->method().' '.rtrim($request->uri()->path(), '/');
    switch ($route) {
      case 'GET /mcp':
        $response->answer(405);
        $response->send('SSE streams not supported', 'text/plain');
        break;

      case 'POST /mcp':
        try {
          $payload= $this->receive($request);
        } catch (FormatException $e) {
          $response->answer(400);
          $response->send('Cannot parse input: '.$e->getMessage(), 'text/plain');
          return;
        }

        switch ($payload['method'] ?? null) {
          case 'initialize':
            $response->answer(200);
            $response->header('Mcp-Session-Id', uniqid(microtime(true)));
            $this->send($response, ['id' => $payload['id'], 'result' => [
              'capabilities'    => $this->capabilities->struct(),
              'serverInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
              'protocolVersion' => $this->version,
            ]]);
            break;

          case 'notifications/initialized':
          case 'notifications/cancelled':
            $response->answer(202);
            $response->header('Content-Length', 0);
            break;

          case 'logging/setLevel':
            $response->answer(200);
            $this->send($response, ['id' => $payload['id'], 'result' => (object)[]]);
            break;

          case 'tools/list':
            $response->answer(200);
            $this->send($response, ['id' => $payload['id'], 'result' => $this->delegates->tools()]);
            break;

          case 'tools/call':
            try {
              $result= $this->delegates->invoke($payload['params']['name'], $payload['params']['arguments']);
              $response->answer(200);
              $this->send($response, [
                'id'     => $payload['id'],
                'result' => ['content' => [['type' => 'text', 'text' => Json::of($result)]]],
              ]);
            } catch (Throwable $t) {
              $response->answer(400);
              $this->send($response, [
                'id'    => $payload['id'],
                'error' => ['code' => -32602, 'message' => $e->getMessage()],
              ]);
            }
            break;

          default:
            $response->answer(400);
            $this->send($response, ['id' => $payload['id'], 'error' => 'unknown_method']);
            break;
        }
        break;

      case 'DELETE /mcp':
        $response->answer(204);
        $response->header('Content-Length', 0);
        break;

      default:
        $response->answer(404);
        $response->send('Cannot handle '.$route, 'text/plain');
        break;
    }
  }
}