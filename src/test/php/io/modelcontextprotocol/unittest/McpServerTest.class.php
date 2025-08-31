<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{InstanceDelegate, ImplementationsIn, Delegates};
use io\modelcontextprotocol\{Capabilities, McpServer};
use lang\FormatException;
use test\{Assert, Before, Expect, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class McpServerTest {
  const PACKAGE= 'io.modelcontextprotocol.unittest';

  private $delegate;

  /** Creates a JSON RPC request with a given message */
  private function rpcRequest($message): Request {
    return new Request(new TestInput(
      'POST',
      '/mcp',
      ['Content-Type' => 'application/json'],
      json_encode(['jsonrpc' => '2.0'] + $message)
    ));
  }

  /** Handle request with a given delegate and return response */
  private function handle(Request $request, $delegate): Response {
    $response= new Response(new TestOutput());
    foreach ((new McpServer($delegate))->handle($request, $response) ?? [] as $_) { }
    return $response;
  }

  #[Before]
  public function delegate() {
    $this->delegate= new class() { };
  }

  #[Test]
  public function delegate_instance() {
    $delegate= new ImplementationsIn(self::PACKAGE);
    Assert::equals($delegate, (new McpServer($delegate))->delegate());
  }

  #[Test]
  public function instance_delegate_for_objects() {
    Assert::equals(new InstanceDelegate($this->delegate), (new McpServer($this->delegate))->delegate());
  }

  #[Test]
  public function delegates_for_arrays() {
    $list= [new InstanceDelegate($this->delegate), new ImplementationsIn(self::PACKAGE)];
    Assert::equals(new Delegates($list), (new McpServer($list))->delegate());
  }

  #[Test]
  public function delegates_for_strings() {
    Assert::equals(new ImplementationsIn(self::PACKAGE), (new McpServer(self::PACKAGE))->delegate());
  }

  #[Test]
  public function default_version() {
    Assert::equals('2025-06-18', (new McpServer($this->delegate))->version());
  }

  #[Test]
  public function default_capabilities() {
    Assert::equals(Capabilities::server(), (new McpServer($this->delegate))->capabilities());
  }

  #[Test]
  public function does_not_support_sse_stream() {
    $response= $this->handle(new Request(new TestInput('GET', '/mcp')), $this->delegate);
    Assert::equals(
      "HTTP/1.1 405 Method Not Allowed\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 25\r\n".
      "\r\n".
      "SSE streams not supported",
      $response->output()->bytes()
    );
  }

  #[Test]
  public function supports_delete() {
    $response= $this->handle(new Request(new TestInput('DELETE', '/mcp')), $this->delegate);
    Assert::equals(
      "HTTP/1.1 204 No Content\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $response->output()->bytes()
    );
  }

  #[Test]
  public function does_not_support_patch() {
    $response= $this->handle(new Request(new TestInput('PATCH', '/mcp')), $this->delegate);
    Assert::equals(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 39\r\n".
      "\r\n".
      "MCP server cannot handle PATCH requests",
      $response->output()->bytes()
    );
  }

  #[Test]
  public function handles_initialize() {
    $request= $this->rpcRequest(['id' => '1', 'method' => 'initialize']);
    $response= $this->handle($request, $this->delegate);

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Mcp-Session-Id: {$response->headers()['Mcp-Session-Id']}\r\n".
      "Content-Type: application/json\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "ae\r\n".
      '{'.
      '"jsonrpc":"2.0","id":"1","result":{'.
      '"capabilities":{"prompts":{},"resources":{},"tools":{}},'.
      '"serverInfo":{"name":"XP\/MCP","version":"1.0.0"},'.
      '"protocolVersion":"2025-06-18"'.
      '}}'.
      "\r\n0\r\n\r\n",
      $response->output()->bytes()
    );
  }

  #[Test]
  public function accepts_initialized_notification() {
    $request= $this->rpcRequest(['id' => '1', 'method' => 'notifications/initialized']);
    $response= $this->handle($request, $this->delegate);

    Assert::equals(
      "HTTP/1.1 202 Accepted\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $response->output()->bytes()
    );
  }

  #[Test]
  public function responds_to_pings() {
    $request= $this->rpcRequest(['id' => '1', 'method' => 'ping']);
    $response= $this->handle($request, $this->delegate);

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Content-Type: application/json\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "26\r\n".
      '{"jsonrpc":"2.0","id":"1","result":{}}'.
      "\r\n0\r\n\r\n",
      $response->output()->bytes()
    );
  }

  #[Test]
  public function supports_arbitrary_paths() {
    $request= $this->rpcRequest(['id' => '1', 'method' => 'ping'])->rewrite('/beta/mcp');
    Assert::equals(200, $this->handle($request, $this->delegate)->status());
  }

  #[Test]
  public function invalid_content_type() {
    $request= new Request(new TestInput(
      'POST',
      '/mcp',
      ['Content-Type' => 'text/plain'],
      'this.is.not.json'
    ));
    $response= $this->handle($request, $this->delegate);
    Assert::equals(
      "HTTP/1.1 400 Bad Request\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 65\r\n".
      "\r\n".
      "lang.FormatException (Expected application/json, have text/plain)",
      $response->output()->bytes()
    );
  }
}