<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Delegates, InstanceDelegate, Tool, Param};
use test\{Assert, Test};

class DelegatesTest extends DelegateTest {

  /** @return io.modelcontextprotocol.server.Delegates */
  protected function fixture() {
    return new Delegates(new InstanceDelegate(new Greetings()));
  }

  #[Test]
  public function includes_tools_of_all_delegates() {
    $basic= new class() {
      #[Tool]
      public function add(
        #[Param(type: 'number')]
        $a,
        #[Param(type: 'number')]
        $b
      ) {
        return $a + $b;
      }
    };
    $statistics= new class() {
      #[Tool]
      public function average(
        #[Param(type: ['type' => 'array', 'items' => 'number'])]
        $numbers
      ) {
        return array_sum($numbers) / sizeof($numbers);
      }
    };

    $fixture= new Delegates(
      new InstanceDelegate($basic, 'basic'),
      new InstanceDelegate($statistics, 'statistics'),
    );

    Assert::equals(
      [
        [
          'name'        => 'basic_add',
          'description' => null,
          'inputSchema' => [
            'type'       => 'object',
            'properties' => [
              'a' => ['type' => 'number'],
              'b' => ['type' => 'number'],
            ],
            'required'   => ['a', 'b'],
          ]
        ],
        [
          'name'        => 'statistics_average',
          'description' => null,
          'inputSchema' => [
            'type'       => 'object',
            'properties' => [
              'numbers' => ['type' => 'array', 'items' => 'number'],
            ],
            'required'   => ['numbers'],
          ]
        ],
      ],
      [...$fixture->tools()]
    );
  }
}