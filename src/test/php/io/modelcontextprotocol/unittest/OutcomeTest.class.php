<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Outcome, Result, Error};
use test\{Assert, Test};

class OutcomeTest {

  #[Test]
  public function from_result() {
    Assert::instance(
      Result::class,
      Outcome::from(['jsonrpc' => '2.0', 'id' => 1, 'result' => 'Test'])
    );
  }

  #[Test]
  public function from_error() {
    Assert::instance(
      Error::class,
      Outcome::from(['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => 404, 'message' => 'Test']])
    );
  }

  #[Test]
  public function result_string() {
    Assert::equals(
      'io.modelcontextprotocol.Result("Test")',
      (new Result('Test'))->toString()
    );
  }

  #[Test]
  public function error_string() {
    Assert::equals(
      'io.modelcontextprotocol.Error(-32700, "Parse error")',
      (new Error(-32700, 'Parse error'))->toString()
    );
  }
}