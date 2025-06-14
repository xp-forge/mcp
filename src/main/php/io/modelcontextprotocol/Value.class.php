<?php namespace io\modelcontextprotocol;

use Traversable;

class Value extends Result {
  private $backing;

  public function __construct($backing) {
    $this->backing= $backing['result'];
  }

  public function getIterator(): Traversable {
    yield 'value' => $this->backing;
  }

  public function first(): ?array {
    return $this->backing;
  }
}