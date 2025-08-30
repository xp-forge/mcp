<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Implementation, Resource, Prompt, Tool, Param};
use util\Bytes;

#[Implementation]
class Greetings {

  /** Default greeting */
  #[Resource('greeting://default')]
  public function default() {
    return 'Hello';
  }

  /** Greeting icon */
  #[Resource(uri: 'greeting://icon', mimeType: 'image/gif', dynamic: true)]
  public function icon() {
    return new Bytes('GIF89...');
  }

  /** Dynamic greeting for a user */
  #[Resource('greeting://user/{name}')]
  public function get($name) {
    return "Hello {$name}";
  }

  /** Greets users */
  #[Prompt]
  public function user(
    #[Param('Whom to greet')]
    $name,
    #[Param(type: ['type' => 'string', 'enum' => ['casual', 'friendly']])]
    $style= 'casual'
  ) {
    return "Write a {$style} greeting for {$name}";
  }

  /** Repeats a given greeting */
  #[Tool]
  public function repeat(
    #[Param]
    $greeting,
    #[Param(type: ['type' => 'number'])]
    $times
  ) {
    return str_repeat($greeting, $times);
  }
}