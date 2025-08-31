<?php namespace io\modelcontextprotocol\server;

/** @see io.modelcontextprotocol.unittest.DelegatesTest */
class Delegates extends Delegate {
  private $delegates= [];

  /** @param (object|array|string|io.modelcontextprotocol.server.Delegate)[]|[:object] $args */
  public function __construct(array $args) {
    if (0 === key($args)) {
      foreach ($args as $arg) {
        $this->delegates[]= parent::from($arg);
      }
    } else {
      foreach ($args as $namespace => $arg) {
        $this->delegates[]= new InstanceDelegate($arg, $namespace);
      }
    }
  }

  /** Finds a readable resource */
  public function readable($uri) {
    foreach ($this->delegates as $delegate) {
      if ($contents= $delegate->readable($uri)) return $contents;
    }
    return null;
  }

  /** Finds an invokeable tool */
  public function invokeable($tool) {
    foreach ($this->delegates as $delegate) {
      if ($callable= $delegate->invokeable($tool)) return $callable;
    }
    return null;
  }

  /** Returns all tools */
  public function tools(): iterable {
    foreach ($this->delegates as $delegate) {
      yield from $delegate->tools();
    }
  }

  /** Returns all prompts */
  public function prompts(): iterable {
    foreach ($this->delegates as $delegate) {
      yield from $delegate->prompts();
    }
  }

  /** Returns all resources */
  public function resources(bool $templates): iterable {
    foreach ($this->delegates as $delegate) {
      yield from $delegate->resources($templates);
    }
  }
}