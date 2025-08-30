<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\JsonRpc;
use lang\FormatException;
use test\{Assert, Expect, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class JsonRpcTest {

  #[Test]
  public function receive_json() {
    $request= new Request(new TestInput(
      'POST',
      '/mcp',
      ['Content-Type' => 'application/json'],
      json_encode(['jsonrpc' => '2.0', 'id' => '1', 'method' => 'initialize'])
    ));

    Assert::equals(
      ['jsonrpc' => '2.0', 'id' => '1', 'method' => 'initialize'],
      (new JsonRpc([]))->receive($request)
    );
  }

  #[Test]
  public function sends_json_chunked() {
    $response= new Response(new TestOutput());
    (new JsonRpc([]))->send($response, ['result' => true]);

    Assert::equals(
      "1f\r\n".'{"jsonrpc":"2.0","result":true}'."\r\n0\r\n\r\n",
      $response->output()->body()
    );
  }

  #[Test, Expect(class: FormatException::class, message: 'Expected application/json, have text/plain')]
  public function raises_error_when_receiving_text() {
    $request= new Request(new TestInput('POST', '/mcp', ['Content-Type' => 'text/plain'], ''));
    (new JsonRpc([]))->receive($request);
  }
}