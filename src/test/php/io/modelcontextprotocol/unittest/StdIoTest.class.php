<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{StdIo, CallFailed};
use lang\Process;
use test\{Assert, Expect, Test, Values};

class StdIoTest {
  use JsonRpc;

  #[Test]
  public function call() {
    $value= ['name' => 'test', 'version' => '1.0.0'];
    $fixture= new StdIo(new Process(PHP_BINARY, ['-r', "echo '{$this->result($value)}', PHP_EOL;"]));

    Assert::equals($value, $fixture->call('test')->value());
  }

  #[Test, Values([8192, 65536, 1048576])]
  public function call_with_output_of_size($size) {
    $fixture= new StdIo(new Process(PHP_BINARY, [
      '-r',
      "echo json_encode(['jsonrpc' => '2.0', 'id' => '6100', 'result' => str_repeat('*', {$size})]), PHP_EOL;"
    ]));

    Assert::equals($size, strlen($fixture->call('test')->value()));
  }

  #[Test, Expect(class: CallFailed::class, message: '#-1: Unexpected EOF from process')]
  public function unexpected_eof() {
    (new StdIo(new Process(PHP_BINARY, ['-r', "exit(0);"])))->call('test');
  }
}