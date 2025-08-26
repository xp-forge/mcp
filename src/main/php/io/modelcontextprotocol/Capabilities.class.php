<?php namespace io\modelcontextprotocol;

use lang\Value;
use util\Objects;

/**
 * MCP Capabilities
 *
 * @see   https://modelcontextprotocol.io/specification/2025-03-26/server
 * @see   https://modelcontextprotocol.io/specification/2025-03-26/client/roots
 * @see   https://modelcontextprotocol.io/specification/2025-03-26/client/sampling
 * @test  io.modelcontextprotocol.unittest.CapabilitiesTest
 */
class Capabilities implements Value {
  private $struct;

  /** @param [:var] $struct */
  public function __construct($struct= []) {
    $this->struct= $struct;
  }

  /** Creates client capabilities */
  public static function client(bool $sampling= true, bool $roots= true, ?array $experimental= null): self {
    return new self([
      'sampling'     => $sampling ? (object)[] : null,
      'roots'        => $roots ? ['listChanged' => true] : null,
      'experimental' => $experimental,
    ]);
  }

  /** Creates server capabilities */
  public static function server() {
    return new self([
      'logging'   => (object)[],
      'prompts'   => (object)[],
      'resources' => (object)[],
      'tools'     => ['listChanged' => true],
    ]);
  }

  /** @return [:var] */
  public function struct() {
    return array_filter($this->struct, fn($value) => null !== $value);
  }

  /**
   * Returns setting for a given feature, or NULL if it's not supported.
   *
   * @return var
   */
  public function setting(string $path) {
    $ptr= &$this->struct;
    foreach (explode('.', $path) as $key) {
      $ptr= &$ptr[$key];
      if (!isset($ptr)) return null;
    }
    return $ptr;
  }

  public function sampling(bool $support): self {
    $this->struct['sampling']= $support ? (object)[] : null;
    return $this;
  }

  public function roots(bool $support): self {
    $this->struct['roots']= $support ? ['listChanged' => true] : null;
    return $this;
  }

  public function experimental(array $support): self {
    $this->struct['experimental']= $support;
    return $this;
  }

  public function hashCode() {
    return 'C'.Objects::hashOf($this->struct);
  }

  public function toString() {
    return nameof($this).'@'.Objects::stringOf($this->struct);
  }

  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->struct(), $value->struct()) : 1;
  }
}