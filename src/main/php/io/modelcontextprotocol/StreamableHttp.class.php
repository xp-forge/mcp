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
   * @return iterable
   * @throws lang.FormatException
   */
  public function call($method, $params= null) {
    call: $response= $this->endpoint->resource('/mcp')->post(
      ['jsonrpc' => '2.0', 'id' => uniqid(), 'method' => $method, 'params' => $params ?? (object)[]],
      self::JSON
    );

    // Handle 200 OK and 401 Unauthorized, any other status code is unexpected.
    if (200 === $response->status()) {

      // If a session header is returned, remember it
      if ($session= $response->header(self::SESSION)) {
        $this->endpoint->with(self::SESSION, $session);
      }

      // Separate content-type value from optional parameters, e.g. "charset"
      $header= $response->header('Content-Type');
      $p= strpos($header, ';');
      switch (false === $p ? $header : rtrim(substr($header, 0, $p))) {
        case self::JSON: yield 'result' => Result::from($response->value());
        case self::EVENTSTREAM: yield 'result' => new EventStream($response->stream());
        default: throw new FormatException('Unexpected content type "'.$header.'"');
      }
    } else if (401 === $response->status()) {
      yield 'authenticate' => $response->header('WWW-Authenticate');
    } else if (404 === $response->status() && ($session= $this->endpoint->headers()[self::SESSION] ?? null)) {

      // Server has terminated the session, indicate session termination to MCP client,
      // which will call initialize, creating a new session. Finally, re-run the call.
      $this->endpoint->with(self::SESSION, null);
      yield 'terminated' => $session;
      goto call;
    } else {
      yield $response->status() => $response->error();
    }
  }

  /** @return void */
  public function close() {
    if (!isset($this->endpoint->headers()[self::SESSION])) return;

    // Clients that no longer need a particular session SHOULD send an HTTP DELETE to the
    // MCP endpoint with the Mcp-Session-Id header, to explicitly terminate the session
    $this->endpoint->resource('/mcp')->delete();
    $this->endpoint->with(self::SESSION, null);
  }
}