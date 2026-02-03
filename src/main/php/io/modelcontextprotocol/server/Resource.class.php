<?php namespace io\modelcontextprotocol\server;

class Resource {
  public $uri, $mimeType, $dynamic;
  public $template, $matches, $struct;

  /**
   * Creates a new resource annotation
   *
   * @param  string $uri
   * @param  string $mimeType
   * @param  bool $dynamic
   * @param  [:var] $meta
   */
  public function __construct($uri, $mimeType= 'text/plain', $dynamic= false, $meta= []) {
    $this->uri= $uri;
    $this->mimeType= $mimeType;
    $this->dynamic= $dynamic;

    if (false === strpos($uri, '{')) {
      $this->template= false;
      $this->struct= ['uri' => $uri, 'dynamic' => $dynamic];
      $this->matches= fn($compare) => $compare === $uri ? (object)[] : null;
    } else {
      $pattern= '#^'.preg_replace(
        ['/\{([^:}]+):([^}]+)\}/', '/\{([^}]+)\}/'],
        ['(?<$1>$2)', '(?<$1>[^/]+)'],
        $this->uri
      ).'#';

      $this->template= true;
      $this->struct= ['uriTemplate' => $uri];
      $this->matches= fn($compare) => preg_match($pattern, $compare, $matches)
        ? array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY)
        : null
      ;
    }

    $meta && $this->struct['_meta']= $meta;
  }
}