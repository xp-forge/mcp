<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Error, Result, Value};
use test\{Assert, Test};

class ResultTest {

  #[Test]
  public function from_result() {
    Assert::instance(
      Value::class,
      Result::from(['jsonrpc' => '2.0', 'id' => 1, 'result' => 'Test'])
    );
  }

  #[Test]
  public function from_error() {
    Assert::instance(
      Error::class,
      Result::from(['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => 404, 'message' => 'Test']])
    );
  }
}