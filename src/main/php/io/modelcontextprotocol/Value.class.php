<?php namespace io\modelcontextprotocol;

use Traversable;

/** A single return value */
class Value extends Result {
  private $backing;

  /** @param [:var] $message JSON-RPC message */
  public function __construct($message) {
    $this->backing= $message['result'];
  }

  /** Yields the underlying value */
  public function getIterator(): Traversable {
    yield 'value' => $this->backing;
  }

  /** Returns the underlying value */
  public function first() {
    return $this->backing;
  }
}