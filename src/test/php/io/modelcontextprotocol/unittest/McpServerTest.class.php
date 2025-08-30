<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Capabilities, McpServer};
use lang\FormatException;
use test\{Assert, Before, Expect, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class McpServerTest {
  private $delegate;

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
    $request= new Request(new TestInput('POST', '/mcp', ['Content-Type' => 'application/json'], '{
      "jsonrpc": "2.0",
      "method": "initialize"
    }'));
    Assert::equals(
      ['jsonrpc' => '2.0', 'method' => 'initialize'],
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
}