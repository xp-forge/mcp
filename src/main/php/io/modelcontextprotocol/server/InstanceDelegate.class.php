<?php namespace io\modelcontextprotocol\server;

use lang\Reflection;

/** @test io.modelcontextprotocol.unittest.InstanceDelegateTest */
class InstanceDelegate extends Delegate {
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

  public function readable($uri) {
    foreach ($this->type->methods() as $method) {
      if ($annotation= $method->annotation(Resource::class)) {
        $resource= $annotation->newInstance();
        if ($segments= ($resource->matches)($uri)) return fn($arguments, $request) => $this->contentsOf(
          $uri,
          $resource->mimeType,
          $this->access($this->instance, $method, $arguments, $request->pass('segments', $segments))
        );
      }
    }
    return null;
  }

  public function invokeable($tool) {
    sscanf($tool, $this->namespace.'_%s', $method);
    if ($reflect= $this->type->method($method)) {
      return fn($arguments, $request) => $this->access($this->instance, $reflect, $arguments, $request);
    }
    return null;
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