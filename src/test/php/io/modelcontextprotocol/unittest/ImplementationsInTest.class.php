<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\ImplementationsIn;
use test\{Assert, Test};

class ImplementationsInTest extends DelegatesTest {
  const PACKAGE= 'io.modelcontextprotocol.unittest';

  /** @return io.modelcontextprotocol.server.Delegates */
  protected function fixture() {
    return new ImplementationsIn(self::PACKAGE);
  }

  #[Test]
  public function can_supply_instantation_function() {
    $created= [];
    $impl= new ImplementationsIn(self::PACKAGE, function($class) use(&$created) {
      $created[]= $class->literal();
      return $class->newInstance();
    });
    Assert::equals([Greetings::class], $created);
  }
}