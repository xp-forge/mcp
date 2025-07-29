<?php namespace io\modelcontextprotocol;

use Traversable;

/** An error */
class Error extends Result {
  public $code, $message;

  public function __construct(string $code, string $message) {
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
}