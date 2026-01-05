<?php namespace io\modelcontextprotocol\server;

/**
 * Result
 *
 * @see   https://modelcontextprotocol.io/specification/2025-11-25/server/tools#tool-result
 * @see   https://modelcontextprotocol.io/specification/2025-11-25/server/tools#structured-content
 * @see   https://modelcontextprotocol.io/specification/2025-11-25/server/tools#error-handling
 * @see   https://modelcontextprotocol.io/specification/2025-11-25/server/tools#annotations
 * @test  io.modelcontextprotocol.unittest.ResultTest
 */
class Result {
  private $struct;

  /** Creates a new result with the given structure */
  public function __construct(array $struct) { $this->struct= $struct; }

  /** Returns the structure */
  public function struct(): array { return $this->struct; }

  /** Creates a result from a given value */
  public static function cast($value): self {
    if ($value instanceof self) {
      return $value;
    } else if (is_scalar($value)) {
      return self::success()->text($value);
    } else {
      return self::structured($value);
    }
  }

  /** Creates a success result */
  public static function success(): self {
    return new self(['content' => []]);
  }

  /** Creates an error result */
  public static function error(): self {
    return new self(['content' => [], 'isError' => true]);
  }

  /** Creates an special unstructured result including a textual JSON-encoded representation */
  public static function structured(array $object): self {
    return new self([
      'structuredContent' => $object,
      'content'           => [['type' => 'text', 'text' => json_encode($object)]],
    ]);
  }

  /** Adds a given typed content */
  public function add(string $type, array $struct, array $annotations= []): self {
    $this->struct['content'][]= ['type' => $type] + $struct + ($annotations
      ? ['annotations' => $annotations]
      : []
    );
    return $this;
  }

  /**
   * Adds a text content
   *
   * @param  string $string
   * @param  [:mixed] $annotations
   */
  public function text($string, $annotations= []): self {
    return $this->add('text', ['text' => (string)$string], $annotations);
  }

  /**
   * Adds an image content
   *
   * @param  string|util.Bytes $data
   * @param  string $mime
   * @param  [:mixed] $annotations
   */
  public function image($data, $mime, $annotations= []): self {
    return $this->add('image', ['data' => base64_encode($data), 'mimeType' => $mime], $annotations);
  }

  /**
   * Adds an audio content
   *
   * @param  string|util.Bytes $data
   * @param  string $mime
   * @param  [:mixed] $annotations
   */
  public function audio($data, $mime, $annotations= []): self {
    return $this->add('audio', ['data' => base64_encode($data), 'mimeType' => $mime], $annotations);
  }

  /**
   * Adds a resource link
   *
   * @param  string $uri
   * @param  string $name
   * @param  string $description
   * @param  string $mime
   * @param  [:mixed] $annotations
   */
  public function link($uri, $name, $description, $mime, $annotations= []): self {
    return $this->add(
      'resource_link',
      ['uri' => $uri, 'name' => $name, 'description' => $description, 'mimeType' => $mime],
      $annotations
    );
  }

  /**
   * Adds an embedded resource
   *
   * @param  string $uri
   * @param  string $mime
   * @param  string|util.Bytes $text
   * @param  [:mixed] $annotations
   */
  public function resource($uri, $mime, $text, $annotations= []): self {
    return $this->add(
      'resource',
      ['resource' => ['uri' => $uri, 'mimeType' => $mime, 'text' => (string)$text]],
      $annotations
    );
  }
}