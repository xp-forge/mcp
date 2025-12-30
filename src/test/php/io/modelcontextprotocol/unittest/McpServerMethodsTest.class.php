<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\McpServer;
use io\modelcontextprotocol\server\{InstanceDelegate, Tool, Param, Value};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

abstract class McpServerMethodsTest {
  const USER= ['uid' => 6100];

  /** Call a given method with arguments on a given implementation */
  protected function method(string $method, array $params, object $impl): string {
    $payload= ['jsonrpc' => '2.0', 'id' => '1', 'method' => $method, 'params' => $params];
    $request= new Request(new TestInput('POST', '/mcp', ['Content-Type' => 'application/json'], json_encode($payload)));
    $response= new Response(new TestOutput());
    $fixture= new McpServer(new InstanceDelegate($impl, 'test'));
    foreach ($fixture->handle($request->pass('user', self::USER), $response) ?? [] as $_) { }

    // Parse JSON from chunked encoding
    return preg_match('/\r\n(.+)\r\n/', $response->output()->body(), $matches)
      ? $matches[1]
      : null
    ;
  }
}