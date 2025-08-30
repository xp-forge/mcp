<?php namespace io\modelcontextprotocol;

use Throwable;
use io\modelcontextprotocol\server\{Delegates, InstanceDelegate};
use lang\FormatException;
use text\json\{StreamInput, StreamOutput};
use util\log\Traceable;
use web\Handler;

/** @test io.modelcontextprotocol.unittest.McpServerTest */
class McpServer implements Handler, Traceable {
  const JSON= 'application/json';

  private $delegates, $version, $capabilities;
  private $cat= null;

  public function __construct($arg, string $version= '2025-03-26') {
    $this->delegates= $arg instanceof Delegates ? $arg : new InstanceDelegate($arg);
    $this->version= $version;
    $this->capabilities= Capabilities::server();
  }

  /** @return string */
  public function version() { return $this->version; }

  /** @return io.modelcontextprotocol.Capabilities */
  public function capabilities() { return $this->capabilities; }

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
    $payload= $input->read();
    $input->close();
    $this->cat && $this->cat->debug('>>>', $payload);
    return $payload;
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
    $output->write($payload);
    $output->close();
    $this->cat && $this->cat->debug('<<<', $payload);
  }

  /**
   * Handle requests
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
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
            $this->send($response, [
              'id'     => $payload['id'],
              'result' => ['tools' => $this->delegates->tools()]
            ]);
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
                'error' => ['code' => -32602, 'message' => $t->getMessage()],
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