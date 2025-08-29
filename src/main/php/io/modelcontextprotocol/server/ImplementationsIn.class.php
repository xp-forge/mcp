<?php namespace io\modelcontextprotocol\server;

use lang\MethodNotImplementedException;
use lang\reflection\Package;

class ImplementationsIn extends Delegates {
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

  public function invoke($tool, $arguments) {
    sscanf($tool, '%[^_]_%s', $namespace, $method);
    if (($type= $this->delegates[$namespace] ?? null) && ($reflect= $type->method($method))) {
      return $reflect->invoke($this->instances[$namespace], (array)$arguments);
    }

    throw new MethodNotImplementedException($tool);
  }

  /** Returns all tools */
  public function tools(): iterable {
    foreach ($this->delegates as $namespace => $type) {
      yield from $this->toolsIn($type, $namespace);
    }
  }
}