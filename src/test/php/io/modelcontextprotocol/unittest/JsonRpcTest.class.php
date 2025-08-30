<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\JsonRpc;
use lang\{FormatException, IllegalAccessException};
use test\{Assert, Expect, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class JsonRpcTest {

  /** Handle fixture and return body */
  private function handle($fixture, $payload) {
    $request= new Request(new TestInput('POST', '/mcp', ['Content-Type' => 'application/json'], $payload));
    $response= new Response(new TestOutput());
    foreach ($fixture->handle($request, $response) ?? [] as $_) { }

    // Parse JSON from chunked encoding
    return preg_match('/\r\n(.+)\r\n/', $response->output()->body(), $matches)
      ? $matches[1]
      : null
    ;
  }

  #[Test]
  public function handle_method() {
    $answer= $this->handle(
      new JsonRpc(['initialize' => fn() => true]),
      '{"jsonrpc":"2.0","id":"1","method":"initialize"}'
    );
    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":true}',
      $answer
    );
  }

  #[Test]
  public function non_existant_handler() {
    $answer= $this->handle(
      new JsonRpc([]),
      '{"jsonrpc":"2.0","id":"1","method":"nonexistant"}'
    );
    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32601,"message":"nonexistant"}}',
      $answer
    );
  }

  #[Test]
  public function errors_raised_by_handler() {
    $answer= $this->handle(
      new JsonRpc(['throw' => function() { throw new IllegalAccessException('Test'); }]),
      '{"jsonrpc":"2.0","id":"1","method":"throw"}'
    );
    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"Test"}}',
      $answer
    );
  }

  #[Test]
  public function handle_malformed_json() {
    Assert::equals(
      '{"jsonrpc":"2.0","id":null,"error":{"code":-32700,"message":"Unexpected token [\"not.json\"] reading value"}}',
      $this->handle(new JsonRpc([]), 'not.json')
    );
  }

  #[Test, Expect(class: FormatException::class, message: 'Expected application/json, have text/plain')]
  public function raises_error_when_receiving_text() {
    (new JsonRpc([]))->handle(
      new Request(new TestInput('POST', '/mcp', ['Content-Type' => 'text/plain'])),
      new Response(new TestOutput())
    );
  }
}