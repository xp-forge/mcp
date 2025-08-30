<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{InstanceDelegate, Tool};
use test\{Assert, Test};

class InstanceDelegateTest extends DelegatesTest {

  /** @return io.modelcontextprotocol.server.Delegates */
  protected function fixture() {
    return new InstanceDelegate(new Greetings());
  }

  #[Test]
  public function can_overwrite_namespace() {
    $impl= new class() {

      #[Tool]
      public function test() { return 'Test worked'; }
    };

    Assert::equals(
      [[
        'name'        => 'tool_test',
        'description' => null,
        'inputSchema' => [
          'type'       => 'object',
          'properties' => (object)[],
          'required'   => [],
        ],
      ]],
      [...(new InstanceDelegate($impl, 'tool'))->tools()]
    );
  }
}