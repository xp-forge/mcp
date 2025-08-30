<?php namespace io\modelcontextprotocol\server;

class Resource {
  public $uri, $mimeType, $dynamic;
  public $template, $matches, $meta;

  /**
   * Creates a new resource annotation
   *
   * @param  string $uri
   * @param  string $mimeType
   * @param  bool $dynamic
   */
  public function __construct($uri, $mimeType= 'text/plain', $dynamic= false) {
    $this->uri= $uri;
    $this->mimeType= $mimeType;
    $this->dynamic= $dynamic;

    if (false === strpos($uri, '{')) {
      $this->template= false;
      $this->meta= ['uri' => $uri, 'mimeType' => $mimeType, 'dynamic' => $dynamic];
      $this->matches= fn($compare) => $compare === $uri ? (object)[] : null;
    } else {
      $pattern= '#^'.preg_replace(
        ['/\{([^:}]+):([^}]+)\}/', '/\{([^}]+)\}/'],
        ['(?<$1>$2)', '(?<$1>[^/]+)'],
        $this->uri
      ).'#';

      $this->template= true;
      $this->meta= ['uriTemplate' => $uri, 'mimeType' => $mimeType];
      $this->matches= fn($compare) => preg_match($pattern, $compare, $matches)
        ? array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY)
        : null
      ;
    }
  }
}