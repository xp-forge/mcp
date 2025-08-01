<?php namespace io\modelcontextprotocol;

use Traversable;

/** A single return value */
class Value extends Result {
  private $result;

  /** @param [:var] $result JSON-RPC result member */
  public function __construct($result) {
    $this->result= $result;
  }

  /** Yields the underlying value */
  public function getIterator(): Traversable {
    yield 'value' => $this;
  }

  /** Returns the underlying value */
  public function value() {
    return $this->result;
  }
}