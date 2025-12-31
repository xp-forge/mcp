<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Tool, Param, Value};
use lang\IllegalStateException;
use test\{Assert, Test};

class McpServerToolCallingTest extends McpServerMethodsTest {

  #[Test]
  public function tool() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() {

      #[Tool]
      public function fixture() {
        return 'Hello World';
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"content":[{"type":"text","text":"Hello World"}]}}',
      $answer
    );
  }

  #[Test]
  public function tool_with_param() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => ['input' => 'Test']], new class() {

      #[Tool]
      public function fixture(
        #[Param]
        $input
      ) {
        return 'Hello '.$input;
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"content":[{"type":"text","text":"Hello Test"}]}}',
      $answer
    );
  }

  #[Test]
  public function tool_with_value() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() {

      #[Tool]
      public function fixture(
        #[Value]
        $user
      ) {
        return 'Hello '.$user['uid'];
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"content":[{"type":"text","text":"Hello 6100"}]}}',
      $answer
    );
  }

  #[Test]
  public function tool_with_named_value() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() {

      #[Tool]
      public function fixture(
        #[Value('user')]
        $auth
      ) {
        return 'Hello '.$auth['uid'];
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"content":[{"type":"text","text":"Hello 6100"}]}}',
      $answer
    );
  }

  #[Test]
  public function tool_raising_error() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() {

      #[Tool]
      public function fixture() {
        throw new IllegalStateException('No access');
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"No access"}}',
      $answer
    );
  }

  #[Test]
  public function missing_tool() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() { });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"test_fixture"}}',
      $answer
    );
  }

  #[Test]
  public function tool_with_missing_param() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() {

      #[Tool]
      public function fixture(
        #[Param]
        $input
      ) {
        return 'Hello '.$input;
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"No default value avaible for parameter $input"}}',
      $answer
    );
  }

  #[Test]
  public function tool_with_missing_value() {
    $answer= $this->method('tools/call', ['name' => 'test_fixture', 'arguments' => []], new class() {

      #[Tool]
      public function fixture(
        #[Value]
        $missing
      ) {
        throw new IllegalStateException('Unreachable');
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"No default value avaible for parameter $missing"}}',
      $answer
    );
  }
}