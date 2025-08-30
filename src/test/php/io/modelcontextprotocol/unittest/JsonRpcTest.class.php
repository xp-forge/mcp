<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\JsonRpc;
use lang\FormatException;
use test\{Assert, Expect, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class JsonRpcTest {

  #[Test]
  public function handle() {
    $request= new Request(new TestInput(
      'POST',
      '/mcp',
      ['Content-Type' => 'application/json'],
      '{"jsonrpc":"2.0","id":"1","method":"initialize"}'
    ));
    $response= new Response(new TestOutput());
    (new JsonRpc(['initialize' => fn() => true]))->handle($request, $response);

    Assert::equals(
      "28\r\n".
      '{"jsonrpc":"2.0","id":"1","result":true}'.
      "\r\n0\r\n\r\n",
      $response->output()->body()
    );
  }

  #[Test]
  public function handle_malformed_json() {
    $request= new Request(new TestInput(
      'POST',
      '/mcp',
      ['Content-Type' => 'application/json'],
      'not.json'
    ));
    $response= new Response(new TestOutput());
    (new JsonRpc(['initialize' => fn() => true]))->handle($request, $response);

    Assert::equals(
      "6d\r\n".
      '{"jsonrpc":"2.0","id":null,"error":{"code":-32700,"message":"Unexpected token [\"not.json\"] reading value"}}'.
      "\r\n0\r\n\r\n",
      $response->output()->body()
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