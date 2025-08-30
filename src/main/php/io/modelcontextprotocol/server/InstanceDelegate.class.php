<?php namespace io\modelcontextprotocol\server;

use lang\{Reflection, MethodNotImplementedException};
use util\NoSuchElementException;

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

  public function read($uri) {
    foreach ($this->type->methods() as $method) {
      if ($annotation= $method->annotation(Resource::class)) {
        $resource= $annotation->newInstance();
        if ($segments= ($resource->matches)($uri)) return $this->contentsOf(
          $uri,
          $resource->mimeType,
          $method->invoke($this->instance, (array)$segments)
        );
      }
    }

    throw new NoSuchElementException($uri);
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

  /** Returns all resources */
  public function resources(bool $templates): iterable {
    yield from $this->resourcesIn($this->type, $this->namespace, $templates);
  }
}