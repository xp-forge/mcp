<?php namespace io\modelcontextprotocol\server;

class Response {
  public $status, $headers, $value;

  /**
   * Creates a response
   *
   * @param  int $status
   * @param  [:string] $headers
   * @param  var $value
   */
  public function __construct($status, $headers, $value= null) {
    $this->status= $status;
    $this->headers= $headers;
    $this->value= $value;
  }
}