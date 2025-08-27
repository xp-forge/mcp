<?php namespace io\modelcontextprotocol\server;

class Param {
  private $description, $type;

  /**
   * Creates a new param annotation
   *
   * @see    https://json-schema.org/understanding-json-schema/reference/type
   * @see    https://json-schema.org/understanding-json-schema/reference/enum
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