<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\ImplementationsIn;
use test\{Assert, Test};

class ImplementationsInTest extends DelegatesTest {

  #[Test]
  public function tools() {
    Assert::equals(
      [self::CALCULATOR],
      [...(new ImplementationsIn('io.modelcontextprotocol.unittest'))->tools()]
    );
  }

  #[Test]
  public function prompts() {
    Assert::equals(
      [self::GREETINGS],
      [...(new ImplementationsIn('io.modelcontextprotocol.unittest'))->prompts()]
    );
  }
}