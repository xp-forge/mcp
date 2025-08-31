<?php namespace io\modelcontextprotocol\server;

/** @see io.modelcontextprotocol.unittest.DelegatesTest */
class Delegates extends Delegate {
  private $delegates;

  public function __construct(parent... $delegates) {
    $this->delegates= $delegates;
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