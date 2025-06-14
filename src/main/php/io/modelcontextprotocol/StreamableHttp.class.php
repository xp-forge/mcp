<?php namespace io\modelcontextprotocol;

use lang\FormatException;
use webservices\rest\Endpoint;

/**
 * Streamable HTTP MCP transport
 * 
 * @see  https://modelcontextprotocol.io/specification/2025-03-26/basic/transports#streamable-http
 * @test io.modelcontextprotocol.unittest.StreamableHttpTest
 */
class StreamableHttp extends Transport {
  const JSON        = 'application/json';
  const EVENTSTREAM = 'text/event-stream';
  const SESSION     = 'Mcp-Session-Id';

  private $endpoint;

  /** @param string|util.URI|webservices.rest.Endpoint $endpoint */
  public function __construct($endpoint) {
    $this->endpoint= $endpoint instanceof Endpoint ? $endpoint : new Endpoint($endpoint);
    $this->endpoint->with(['Accept' => 'text/event-stream, application/json']);
  }

  /** @param ?util.log.LogCategory */
  public function setTrace($cat) {
    $this->endpoint->setTrace($cat);
  }

  /**
   * Sends a notification
   *
   * @param  string $method
   * @return void
   * @throws io.modelcontextprotocol.CallFailed
   */
  public function notify($method) {
    $response= $this->endpoint->resource('/mcp')->post(['jsonrpc' => '2.0', 'method' => $method], self::JSON);
    if (202 !== $response->status()) throw new CallFailed($response->status(), $response->error());
  }

  /**
   * Calls a method
   *
   * @param  string $method
   * @param  ?[:string] $params
   * @return io.modelcontextprotocol.Result
   * @throws io.modelcontextprotocol.CallFailed
   * @throws lang.FormatException
   */
  public function call($method, $params= null) {
    $response= $this->endpoint->resource('/mcp')->post(
      ['jsonrpc' => '2.0', 'id' => uniqid(), 'method' => $method, 'params' => $params ?? (object)[]],
      self::JSON
    );
    if (200 !== $response->status()) throw new CallFailed($response->status(), $response->error());

    // If a session header is returned, remember it
    if ($session= $response->header(self::SESSION)) {
      $this->endpoint->with(self::SESSION, $session);
    }

    // Separate content-type value from optional parameters, e.g. "charset"
    $header= $response->header('Content-Type');
    $p= strpos($header, ';');
    switch (false === $p ? $header : rtrim(substr($header, 0, $p))) {
      case self::JSON: return Result::from($response->value());
      case self::EVENTSTREAM: return new EventStream($response->stream());
      default: throw new FormatException('Unexpected content type "'.$header.'"');
    }
  }

  /** @return void */
  public function close() {
    $this->endpoint->with(self::SESSION, null);
  }
}