<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{StdIo, CallFailed};
use lang\Process;
use test\{Assert, Expect, Test};

class StdIoTest {
  use JsonRpc;

  #[Test]
  public function call() {
    $value= ['name' => 'test', 'version' => '1.0.0'];
    $fixture= new StdIo(new Process(PHP_BINARY, ['-r', "echo '{$this->result($value)}', PHP_EOL;"]));

    Assert::equals($value, $fixture->call('test')->first());
  }

  #[Test, Expect(class: CallFailed::class, message: '#-1: Unexpected EOF from process')]
  public function unexpected_eof() {
    (new StdIo(new Process(PHP_BINARY, ['-r', "exit(0);"])))->call('test');
  }
}