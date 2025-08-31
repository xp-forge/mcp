<?php namespace io\modelcontextprotocol;

use Traversable;

/** An error */
class Error extends Result {
  public $code, $message;

  /**
   * Creates a new error instance
   *
   * @param  int|string $code
   * @param  string $message
   */
  public function __construct($code, $message) {
    $this->code= $code;
    $this->message= $message;
  }

  /** Yields the underlying value */
  public function getIterator(): Traversable {
    throw new CallFailed($this->code, $this->message);
  }

  /** Returns the underlying value */
  public function value() {
    throw new CallFailed($this->code, $this->message);
  }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->code.', "'.$this->message.'")'; }

  /** @return string */
  public function hashCode() { return 'E'.md5($this->code.':'.$this->message); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? $this->code.':'.$this->message <=> $value->code.':'.$value->message
      : 1
    ;
  }
}