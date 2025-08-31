<?php namespace io\modelcontextprotocol;

use Traversable;
use util\Objects;

/** A single result value */
class Result extends Outcome {
  private $value;

  /** @param [:var] $value JSON-RPC result member */
  public function __construct($value) { $this->value= $value; }

  /** Yields the underlying value */
  public function getIterator(): Traversable { yield 'value' => $this; }

  /** Returns the underlying value */
  public function value() { return $this->value; }

  /** @return string */
  public function toString() { return nameof($this).'('.Objects::stringOf($this->value).')'; }

  /** @return string */
  public function hashCode() { return 'R'.Objects::hashOf($this->value); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->value, $value->value) : 1;
  }
}