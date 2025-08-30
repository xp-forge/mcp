<?php namespace io\modelcontextprotocol\unittest;

abstract class DelegatesTest {
  const CALCULATOR= [
    'name'        => 'calculator_add',
    'description' => 'Adds two numbers',
    'inputSchema' => [
      'type'       => 'object',
      'properties' => [
        'lhs' => ['type' => 'number'],
        'rhs' => ['type' => 'number'],
      ],
      'required' => ['lhs', 'rhs'],
    ]
  ];
  const GREETINGS= [
    'name'        => 'greetings_get',
    'description' => 'Gets greeting',
    'arguments' => [
      [
        'name'        => 'name',
        'description' => 'Whom to greet',
        'required'    => true,
        'schema'      => ['type' => 'string'],
      ],
      [
        'name'        => 'style',
        'description' => 'Style',
        'required'    => false,
        'schema'      => ['type' => 'string', 'enum' => ['casual', 'friendly']],
      ],
    ],
  ];
}