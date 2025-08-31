<?php namespace io\modelcontextprotocol;

use io\modelcontextprotocol\server\{Delegate, Delegates, InstanceDelegate, JsonRpc, Response};
use lang\FormatException;
use text\json\Json;
use util\NoSuchElementException;
use util\log\Traceable;
use web\Handler;

/** @test io.modelcontextprotocol.unittest.McpServerTest */
class McpServer implements Handler, Traceable {
  private $delegate, $version, $capabilities, $rpc;
  private $cat= null;

  public function __construct($arg, string $version= '2025-06-18') {
    if ($arg instanceof Delegate) {
      $this->delegate= $arg;
    } else if (is_array($arg)) {
      $this->delegate= new Delegates(...$arg);
    } else {
      $this->delegate= new InstanceDelegate($arg);
    }

    $this->version= $version;
    $this->capabilities= Capabilities::server();

    $this->rpc= new JsonRpc([
      'initialize' => function() {
        return new Response(200, ['Mcp-Session-Id' => uniqid(microtime(true))], [
          'capabilities'    => $this->capabilities->struct(),
          'serverInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
          'protocolVersion' => $this->version,
        ]);
      },
      'tools/list' => function() {
        return ['tools' => $this->delegate->tools()];
      },
      'prompts/list' => function() {
        return ['prompts' => $this->delegate->prompts()];
      },
      'resources/list' => function() {
        return ['resources' => $this->delegate->resources(false)];
      },
      'resources/templates/list' => function() {
        return ['resourceTemplates' => $this->delegate->resources(true)];
      },
      'tools/call' => function($payload) {
        if ($invokeable= $this->delegate->invokeable($payload['params']['name'])) {
          $result= $invokeable((array)$payload['params']['arguments']);
          return ['content' => [['type' => 'text', 'text' => is_string($result)
            ? $result
            : Json::of($result)
          ]]];
        }
        throw new NoSuchElementException($payload['params']['name']);
      },
      'prompts/get' => function($payload) {
        if ($invokeable= $this->delegate->invokeable($payload['params']['name'])) {
          $result= $invokeable((array)$payload['params']['arguments']);
          return ['messages' => is_iterable($result)
            ? $result
            : [['role' => 'user', 'content' => ['type' => 'text', 'text' => $result]]]
          ];
        }
        throw new NoSuchElementException($payload['params']['name']);
      },
      'resources/read' => function($payload) {
        if ($contents= $this->delegate->readable($payload['params']['uri'])) {
          return ['contents' => $contents];
        }
        throw new NoSuchElementException($payload['params']['uri']);
      },
      'notifications/(.*)' => function() {
        return new Response(202, ['Content-Length' => 0]);
      },
      'ping' => function() {
        return (object)[];
      },
    ]);
  }

  /** @return io.modelcontextprotocol.server.Delegate */
  public function delegate() { return $this->delegate; }

  /** @return string */
  public function version() { return $this->version; }

  /** @return io.modelcontextprotocol.Capabilities */
  public function capabilities() { return $this->capabilities; }

  /** @param util.log.LogCategory $cat */
  public function setTrace($cat) {
    $this->rpc->setTrace($cat);
  }

  /**
   * Handle requests
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function handle($request, $response) {
    switch ($request->method()) {
      case 'POST':
        try {
          return $this->rpc->handle($request, $response);
        } catch (FormatException $t) {
          $response->answer(400);
          $response->send(nameof($t)." ({$t->getMessage()})", 'text/plain');
        }
        break;

      case 'GET':
        $response->answer(405);
        $response->send('SSE streams not supported', 'text/plain');
        break;

      case 'DELETE':
        $response->answer(204);
        $response->header('Content-Length', 0);
        $response->flush();
        break;

      default:
        $response->answer(404);
        $response->send("MCP server cannot handle {$request->method()} requests", 'text/plain');
        break;
    }
  }
}