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

  /** Maps a value to result */
  private static function of($value) {
    if (null === $value) {
      return ['content' => []];
    } else if (is_scalar($value)) {
      return ['content' => [['type' => 'text', 'text' => (string)$value]]];
    } else {
      return ['structuredContent' => $value];
    }
  }

  /** Creates a result from a given value */
  public static function cast($value): self {
    return $value instanceof self ? $value : new self(self::of($value));
  }

  /** Creates a success result */
  public static function success($value= null): self {
    return new self(self::of($value));
  }

  /** Creates an error result */
  public static function error($value= null): self {
    return new self(self::of($value) + ['isError' => true]);
  }

  /**
   * Creates an special structured result including a textual representation,
   * which defaults to a JSON-serialized version of the given object.
   *
   * @param  var $object
   * @param  ?string|iterable $text
   * @param  ?bool $isError
   */
  public static function structured($object, $text= null, $isError= null): self {
    $self= new self(['content' => [], 'structuredContent' => $object]);

    if (null === $text) {
      $self->text(json_encode($object));
    } else if (is_iterable($text)) {
      foreach ($text as $part) {
        $self->text($part);
      }
    } else {
      $self->text($text);
    }

    isset($isError) && $self->struct['isError']= (bool)$isError;
    return $self;
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
   * @param  string|util.Bytes $text
   * @param  string $mime
   * @param  [:mixed] $annotations
   */
  public function resource($uri, $text, $mime, $annotations= []): self {
    return $this->add(
      'resource',
      ['resource' => ['uri' => $uri, 'text' => (string)$text, 'mimeType' => $mime]],
      $annotations
    );
  }

  /** Returns the structure */
  public function struct(): array { return $this->struct; }
}