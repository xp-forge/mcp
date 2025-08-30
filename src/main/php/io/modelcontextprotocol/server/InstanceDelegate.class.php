<?php namespace io\modelcontextprotocol\server;

use lang\{Reflection, MethodNotImplementedException};

/** @test io.modelcontextprotocol.unittest.InstanceDelegateTest */
class InstanceDelegate extends Delegates {
  private $instance, $type;
  private $namespace= null;

  public function __construct(object $instance, ?string $namespace= null) {
    $this->instance= $instance;
    $this->type= Reflection::type($instance);
    $this->namespace= (
      $namespace ??
      (($impl= $this->type->annotation(Implementation::class)) ? $impl->argument(0) : null) ??
      strtolower($this->type->declaredName())
    );
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

  /** Returns all prompts */
  public function prompts(): iterable {
    yield from $this->promptsIn($this->type, $this->namespace);
  }
}