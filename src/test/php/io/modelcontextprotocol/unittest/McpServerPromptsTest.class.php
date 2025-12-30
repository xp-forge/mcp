<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Tool, Prompt, Param, Value};
use lang\IllegalStateException;
use test\{Assert, Test};

class McpServerPromptsTest extends McpServerMethodsTest {

  #[Test]
  public function prompt() {
    $answer= $this->method('prompts/get', ['name' => 'test_greeting', 'arguments' => []], new class() {

      #[Prompt]
      public function greeting() {
        return 'Hello World';
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"messages":[{"role":"user","content":{"type":"text","text":"Hello World"}}]}}',
      $answer
    );
  }

  #[Test]
  public function prompt_with_param() {
    $answer= $this->method('prompts/get', ['name' => 'test_greeting', 'arguments' => ['user' => 'Test']], new class() {

      #[Prompt]
      public function greeting(
        #[Param]
        $user
      ) {
        return 'Hello '.$user;
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"messages":[{"role":"user","content":{"type":"text","text":"Hello Test"}}]}}',
      $answer
    );
  }

  #[Test]
  public function prompt_with_value() {
    $answer= $this->method('prompts/get', ['name' => 'test_greeting', 'arguments' => []], new class() {

      #[Prompt]
      public function greeting(
        #[Value]
        $user
      ) {
        return 'Hello '.$user['uid'];
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"messages":[{"role":"user","content":{"type":"text","text":"Hello 6100"}}]}}',
      $answer
    );
  }

  #[Test]
  public function missing_prompt() {
    $answer= $this->method('prompts/get', ['name' => 'test_greeting', 'arguments' => []], new class() { });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"test_greeting"}}',
      $answer
    );
  }
}