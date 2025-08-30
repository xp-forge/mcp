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

  /**
   * Creates client capabilities
   *
   * @param  bool|array $sampling
   * @param  bool|array $roots
   * @param  bool|array $experimental
   */
  public static function client($sampling= true, $roots= true, $experimental= null): self {
    return new self([
      'sampling'     => is_array($sampling) ? $sampling : ($sampling ? (object)[] : null),
      'roots'        => is_array($roots) ? $roots : ($roots ? (object)[] : null),
      'experimental' => is_array($experimental) ? $experimental : ($experimental ? (object)[] : null),
    ]);
  }

  /** Creates server capabilities */
  public static function server() {
    return new self([
      'logging'   => null,
      'prompts'   => (object)[],
      'resources' => (object)[],
      'tools'     => (object)[],
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
      if (is_object($ptr)) {
        $ptr= &$ptr->{$key};
      } else {
        $ptr= &$ptr[$key];
      }
      if (!isset($ptr)) return null;
    }
    return $ptr;
  }

  /** @param bool|array $support */
  public function sampling($support): self {
    $this->struct['sampling']= is_array($support) ? $support : ($support ? (object)[] : null);
    return $this;
  }

  /** @param bool|array $support */
  public function roots($support): self {
    $this->struct['roots']= is_array($support) ? $support : ($support ? (object)[] : null);
    return $this;
  }

  /** @param bool|array $support */
  public function experimental($support): self {
    $this->struct['experimental']= is_array($support) ? $support : ($support ? (object)[] : null);
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