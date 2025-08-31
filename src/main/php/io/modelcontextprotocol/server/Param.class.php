<?php namespace io\modelcontextprotocol\server;

/** @see https://json-schema.org/understanding-json-schema/reference */
class Param {
  private $description, $type;

  /**
   * Creates a new param annotation
   *
   * @param  ?string $description
   * @param  string|[:var] $type
   */
  public function __construct($description= null, $type= 'string') {
    $this->description= $description;
    $this->type= is_array($type) ? $type : ['type' => $type];
  }

  /** Returns parameter schema */
  public function schema(): array {
    return null === $this->description ? $this->type : $this->type + ['description' => $this->description];
  }
}