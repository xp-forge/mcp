<?php namespace io\modelcontextprotocol\server;

use lang\reflection\Package;

/** @test io.modelcontextprotocol.unittest.ImplementationsInTest */
class ImplementationsIn extends Delegate {
  private $delegates= [];
  private $instances= [];
  private $new;

  /**
   * Creates this delegates instance
   *
   * @param  lang.reflection.Package|string $package
   * @param  function(lang.XPClass): object $new Optional function to create instances
   */
  public function __construct($package, $new= null) {
    $p= $package instanceof Package ? $package : new Package($package);
    foreach ($p->types() as $type) {
      if ($impl= $type->annotation(Implementation::class)) {
        $name= $impl->argument(0) ?? strtolower($type->declaredName());
        $this->delegates[$name]= $type;
        $this->instances[$name]= $new ? $new($type->class()) : $type->newInstance();
      }
    }
  }

  public function readable($uri) {
    foreach ($this->delegates as $namespace => $type) {
      foreach ($type->methods() as $method) {
        if ($annotation= $method->annotation(Resource::class)) {
          $resource= $annotation->newInstance();
          if ($segments= ($resource->matches)($uri)) return fn($arguments, $request) => $this->contentsOf(
            $uri,
            $resource->mimeType,
            $this->access($this->instances[$namespace], $method, $arguments, $request->pass('segments', $segments))
          );
        }
      }
    }
    return null;
  }

  public function invokeable($tool) {
    sscanf($tool, '%[^_]_%s', $namespace, $method);
    if (($type= $this->delegates[$namespace] ?? null) && ($reflect= $type->method($method))) {
      return fn($arguments, $request) => $this->access($this->instances[$namespace], $reflect, $arguments, $request);
    }
    return null;
  }

  /** Returns all tools */
  public function tools(): iterable {
    foreach ($this->delegates as $namespace => $type) {
      yield from $this->toolsIn($type, $namespace);
    }
  }

  /** Returns all prompts */
  public function prompts(): iterable {
    foreach ($this->delegates as $namespace => $type) {
      yield from $this->promptsIn($type, $namespace);
    }
  }

  /** Returns all resources */
  public function resources(bool $templates): iterable {
    foreach ($this->delegates as $namespace => $type) {
      yield from $this->resourcesIn($type, $namespace, $templates);
    }
  }
}