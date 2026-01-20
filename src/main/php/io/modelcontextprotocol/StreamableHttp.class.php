<?php namespace io\modelcontextprotocol;

use lang\FormatException;
use webservices\rest\Endpoint;
use util\URI;

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

  private $endpoint, $path;

  /** @param string|util.URI|webservices.rest.Endpoint $endpoint */
  public function __construct($endpoint) {

    // Use path from endpoint to prevent trailing `/` being added
    if ($endpoint instanceof Endpoint) {
      $this->endpoint= $endpoint;
      $this->path= $endpoint->base()->path();
    } else if ($endpoint instanceof URI) {
      $this->endpoint= new Endpoint($endpoint);
      $this->path= $endpoint->path();
    } else {
      $this->endpoint= new Endpoint($endpoint);
      $this->path= (new URI($endpoint))->path();
    }

    $this->endpoint->with(['Accept' => 'text/event-stream, application/json']);
  }

  /** @param ?util.log.LogCategory */
  public function setTrace($cat) {
    $this->endpoint->setTrace($cat);
  }

  /** @param string $version */ 
  public function version($version) {
    $this->endpoint->with(['MCP-Protocol-Version' => $version]);
  }

  /** @return [:string] */
  public function headers() { return $this->endpoint->headers(); }

  /**
   * Adds headers to be sent with every request
   *
   * @param  string|[:string] $arg
   * @param  ?string $value
   * @return self
   */
  public function with($arg, $value= null) {
    $this->endpoint->with($arg, $value);
    return $this;
  }

  /**
   * Sends a notification
   *
   * @param  string $method
   * @return void
   * @throws io.modelcontextprotocol.CallFailed
   */
  public function notify($method) {
    $response= $this->endpoint->resource($this->path)->post(['jsonrpc' => '2.0', 'method' => $method], self::JSON);
    if (202 !== $response->status()) throw new CallFailed($response->status(), $response->content());
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
    call: $response= $this->endpoint->resource($this->path)->post(
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
        case self::JSON: yield 'result' => Outcome::from($response->value());
        case self::EVENTSTREAM: yield 'result' => new EventStream($response->stream());
        default: throw new FormatException('Unexpected content type "'.$header.'"');
      }
    } else if (401 === $response->status()) {
      yield 'authorize' => $response->header('WWW-Authenticate');
    } else if (404 === $response->status() && ($session= $this->endpoint->headers()[self::SESSION] ?? null)) {

      // Server has terminated the session, indicate session termination to MCP client,
      // which will call initialize, creating a new session. Finally, re-run the call.
      $this->endpoint->with(self::SESSION, null);
      yield 'terminated' => $session;
      goto call;
    } else {
      yield $response->status() => $response->content();
    }
  }

  /** @return void */
  public function close() {
    if (!isset($this->endpoint->headers()[self::SESSION])) return;

    // Clients that no longer need a particular session SHOULD send an HTTP DELETE to the
    // MCP endpoint with the Mcp-Session-Id header, to explicitly terminate the session
    $this->endpoint->resource($this->path)->delete();
    $this->endpoint->with(self::SESSION, null);
  }
}