<?php namespace io\modelcontextprotocol\server;

use lang\{Reflection, MethodNotImplementedException};

class InstanceDelegate extends Delegates {
  private $instance, $type;
  private $namespace= null;

  public function __construct(object $instance) {
    $this->instance= $instance;
    $this->type= Reflection::type($instance);

    // Derive name from annotation if present
    if ($impl= $this->type->annotation(Implementation::class)) {
      $this->namespace= $impl->argument(0);
    }
    $this->namespace??= strtolower($this->type->declaredName());
  }

  public function invoke($tool, $arguments) {
    sscanf($tool, $this->namespace.'_%s', $method);
    if ($reflect= $this->type->method($method)) {
      return $reflect->invoke($this->instance, (array)$arguments);
    }

    throw new MethodNotImplementedException($tool);
  }

  /** Returns all tools */
  public function tools(): iterable {
    yield from $this->toolsIn($this->type, $this->namespace);
  }
}