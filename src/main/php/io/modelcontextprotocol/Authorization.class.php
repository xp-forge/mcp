<?php namespace io\modelcontextprotocol;

use Traversable;
use lang\FormatException;

/** 
 * MCP servers MUST use the HTTP header WWW-Authenticate when returning
 * a 401 Unauthorized to indicate the location of the resource server
 * metadata URL.
 *
 * @see  https://blog.christianposta.com/understanding-mcp-authorization-with-dynamic-client-registration/
 * @test io.modelcontextprotocol.unittest.AuthorizationTest
 */
class Authorization extends Result {
  public $scheme, $parameters;

  public function __construct(string $scheme, array $parameters) {
    $this->scheme= $scheme;
    $this->parameters= $parameters;
  }

  /** Returns header */
  public function header(): string {
    $p= '';
    foreach ($this->parameters as $name => $value) {
      $p.= ', '.$name.'="'.strtr($value, ['"' => '\"']).'"';
    }
    return $this->scheme.substr($p, 1);
  }

  /**
   * Parses from a header
   *
   * @throws lang.FormatException
   */
  public static function parse(string $header): self {
    $l= strlen($header);
    $offset= strcspn($header, ' ');
    $scheme= substr($header, 0, $offset);

    $offset++;
    $parameters= [];
    do {
      $s= strcspn($header, '=', $offset);
      if ($offset + $s >= $l) {
        throw new FormatException('Could not find "="');
      }

      $name= trim(substr($header, $offset, $s));
      $offset+= $s + 1;

      if ('"' === $header[$offset]) {
        $p= $offset + 1;
        do {
          if (false === ($p= strpos($header, '"', $p))) {
            throw new FormatException('Unclosed string in parameter "'.$name.'"');
          }
        } while ('\\' === $header[$p++ - 1]);

        $parameters[$name]= strtr(substr($header, $offset + 1, $p - $offset - 2), ['\"' => '"']);
        $offset= $p + 1;
      } else {
        $s= strcspn($header, ',', $offset);
        $parameters[$name]= substr($header, $offset, $s);
        $offset+= $s + 1;
      }
    } while ($offset < $l && ',' === $header[$offset - 1]);

    return new self($scheme, $parameters);
  }

  /** Yields the underlying value */
  public function getIterator(): Traversable {
    throw new CallFailed(401, $this->header());
  }

  /** Returns the underlying value */
  public function value() {
    throw new CallFailed(401, $this->header());
  }
}