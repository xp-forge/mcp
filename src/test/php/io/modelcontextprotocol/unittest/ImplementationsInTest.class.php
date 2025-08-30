<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\ImplementationsIn;
use test\{Assert, Test};

class ImplementationsInTest extends DelegatesTest {
  const PACKAGE= 'io.modelcontextprotocol.unittest';

  #[Test]
  public function can_supply_instantation_function() {
    $created= [];
    $impl= new ImplementationsIn(self::PACKAGE, function($class) use(&$created) {
      $created[]= $class->literal();
      return $class->newInstance();
    });

    sort($created);
    Assert::equals([Calculator::class, Greetings::class], $created);
  }

  #[Test]
  public function tools() {
    Assert::equals(
      [self::CALCULATOR],
      [...(new ImplementationsIn(self::PACKAGE))->tools()]
    );
  }

  #[Test]
  public function prompts() {
    Assert::equals(
      [self::GREETINGS],
      [...(new ImplementationsIn(self::PACKAGE))->prompts()]
    );
  }
}