<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Implementation, Prompt, Param};

#[Implementation]
class Greetings {

  /** Gets greeting */
  #[Prompt]
  public function get(
    #[Param('Whom to greet')]
    $name,
    #[Param(type: ['type' => 'string', 'enum' => ['casual', 'friendly']])]
    $style= 'casual'
  ) {
    return "Write a {$style} greeting for {$name}";
  }
}