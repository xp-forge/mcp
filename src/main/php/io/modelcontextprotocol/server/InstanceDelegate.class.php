<?php namespace io\modelcontextprotocol\server;

use lang\{Reflection, MethodNotImplementedException};

class InstanceDelegate {
  private $instance;

  public function __construct(object $instance) {
    $this->instance= $instance;
  }

  public function invoke($method, $arguments) {
    if ($reflect= Reflection::type($this->instance)->method($method)) {
      return $reflect->invoke($this->instance, (array)$arguments);
    }

    throw new MethodNotImplementedException($method);
  }

  public function tools() {
    $tools= [];
    foreach (Reflection::type($this->instance)->methods() as $name => $method) {
      if ($method->annotation(Tool::class)) {
        $tools[]= [
          'name'        => $name,
          'description' => $method->comment() ?? null,
          'inputSchema' => [
            'type'       => 'object',
            'properties' => (object)[],
            'required'   => [],
          ],
        ];
      }
    }
    return ['tools' => $tools];
  }
}