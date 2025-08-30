<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Capabilities, McpServer};
use lang\FormatException;
use test\{Assert, Before, Expect, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class McpServerTest {
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
  public function default_version() {
    Assert::equals('2025-03-26', (new McpServer($this->delegate))->version());
  }

  #[Test]
  public function default_capabilities() {
    Assert::equals(Capabilities::server(), (new McpServer($this->delegate))->capabilities());
  }

  #[Test]
  public function receive_json() {
    $request= $this->rpcRequest(['id' => '1', 'method' => 'initialize']);
    Assert::equals(
      ['jsonrpc' => '2.0', 'id' => '1', 'method' => 'initialize'],
      (new McpServer($this->delegate))->receive($request)
    );
  }

  #[Test]
  public function sends_json_chunked() {
    $response= new Response(new TestOutput());
    (new McpServer($this->delegate))->send($response, ['result' => true]);
    Assert::equals(
      "1f\r\n".'{"jsonrpc":"2.0","result":true}'."\r\n0\r\n\r\n",
      $response->output()->body()
    );
  }

  #[Test, Expect(class: FormatException::class, message: 'Expected application/json, have text/plain')]
  public function raises_error_when_receiving_text() {
    $request= new Request(new TestInput('POST', '/mcp', ['Content-Type' => 'text/plain'], ''));
    (new McpServer($this->delegate))->receive($request);
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
      '"protocolVersion":"2025-03-26"'.
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
}