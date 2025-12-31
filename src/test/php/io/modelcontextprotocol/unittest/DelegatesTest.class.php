<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Delegates, Tool, Param};
use test\{Assert, Test};

class DelegatesTest extends DelegateTest {

  /** @return io.modelcontextprotocol.server.Delegates */
  protected function fixture() {
    return new Delegates([new Greetings()]);
  }

  #[Test]
  public function includes_tools_of_all_delegates() {
    $fixture= new Delegates([
      'basic' => new class() {

        /** Calculates sum of the given numbers */
        #[Tool]
        public function add(
          #[Param(type: 'number')]
          $a,
          #[Param(type: 'number')]
          $b
        ) {
          return $a + $b;
        }
      },
      'statistics' => new class() {

        /** Calculates average of the given numbers */
        #[Tool]
        public function average(
          #[Param(type: ['type' => 'array', 'items' => 'number'])]
          $numbers
        ) {
          return array_sum($numbers) / sizeof($numbers);
        }
      }
    ]);

    Assert::equals(
      [
        [
          'name'        => 'basic_add',
          'description' => 'Calculates sum of the given numbers',
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
          'description' => 'Calculates average of the given numbers',
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