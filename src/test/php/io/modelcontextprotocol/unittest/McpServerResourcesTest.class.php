<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Resource, Value};
use lang\IllegalStateException;
use test\{Assert, Test};

class McpServerResourcesTest extends McpServerMethodsTest {

  #[Test]
  public function resource() {
    $answer= $this->method('resources/read', ['uri' => 'greeting://user'], new class() {

      #[Resource('greeting://user')]
      public function greeting() {
        return 'Hello Test';
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"contents":[{"uri":"greeting:\/\/user","mimeType":"text\/plain","text":"Hello Test"}]}}',
      $answer
    );
  }

  #[Test]
  public function resource_with_segment() {
    $answer= $this->method('resources/read', ['uri' => 'greeting://user/test'], new class() {

      #[Resource('greeting://user/{name}')]
      public function greeting($name) {
        return 'Hello '.$name;
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"contents":[{"uri":"greeting:\/\/user\/test","mimeType":"text\/plain","text":"Hello test"}]}}',
      $answer
    );
  }

  #[Test]
  public function resource_with_value() {
    $answer= $this->method('resources/read', ['uri' => 'greeting://user/me'], new class() {

      #[Resource('greeting://user/me')]
      public function greeting(
        #[Value]
        $user
      ) {
        return 'Hello '.$user['uid'];
      }
    });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","result":{"contents":[{"uri":"greeting:\/\/user\/me","mimeType":"text\/plain","text":"Hello 6100"}]}}',
      $answer
    );
  }

  #[Test]
  public function missing_resourcce() {
    $answer= $this->method('resources/read', ['uri' => 'greeting://user/test'], new class() { });

    Assert::equals(
      '{"jsonrpc":"2.0","id":"1","error":{"code":-32600,"message":"greeting:\/\/user\/test"}}',
      $answer
    );
  }
}