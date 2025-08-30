<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{InstanceDelegate, Tool, Prompt, Param};
use test\{Assert, Test};

class InstanceDelegateTest {

  #[Test]
  public function tools() {
    $impl= new class() {

      /** Adds two numbers */
      #[Tool]
      public function add(
        #[Param(type: 'number')]
        $lhs,
        #[Param(type: 'number')]
        $rhs
      ) {
        return $lhs + $rhs;
      }
    };

    Assert::equals(
      [[
        'name'        => 'test_add',
        'description' => 'Adds two numbers',
        'inputSchema' => [
          'type'       => 'object',
          'properties' => [
            'lhs' => ['type' => 'number'],
            'rhs' => ['type' => 'number'],
          ],
          'required' => ['lhs', 'rhs'],
        ]
      ]],
      [...(new InstanceDelegate($impl, 'test'))->tools()]
    );
  }

  #[Test]
  public function prompts() {
    $impl= new class() {

      /** Gets greeting */
      #[Prompt]
      public function greeting(
        #[Param('Whom to greet')]
        $name
      ) {
        return "Hello {$name}";
      }
    };

    Assert::equals(
      [[
        'name'        => 'test_greeting',
        'description' => 'Gets greeting',
        'arguments' => [[
          'name'        => 'name',
          'description' => 'Whom to greet',
          'required'    => true,
          'schema'      => ['type' => 'string'],
        ]],
      ]],
      [...(new InstanceDelegate($impl, 'test'))->prompts()]
    );
  }
}