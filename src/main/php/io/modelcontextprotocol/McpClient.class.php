<?php namespace io\modelcontextprotocol;

use util\log\Traceable;

/**
 * Model Context Protocol client
 * 
 * @see  https://deadprogrammersociety.com/2025/03/calling-mcp-servers-the-hard-way.html
 * @test io.modelcontextprotocol.unittest.McpClientTest
 */
class McpClient implements Traceable {
  private $transport, $version;
  private $server= null;

  /**
   * Creates a new MCP client with a given endpoint and protocol version
   *
   * @param  string|array|util.URI|io.modelcontextprotocol.Transport $endpoint
   * @param  string $version
   */
  public function __construct($endpoint, string $version= '2025-03-26') {
    $this->transport= $endpoint instanceof Transport ? $endpoint : Transport::for($endpoint);
    $this->version= $version;
  }

  /** @return io.modelcontextprotocol.Transport */
  public function transport() { return $this->transport; }

  /** @return string */
  public function version() { return $this->version; }

  /** @param ?util.log.LogCategory */
  public function setTrace($cat) {
    $this->transport->setTrace($cat);
  }

  /**
   * The initialization phase MUST be the first interaction between client and server
   *
   * @return var
   */
  public function initialize() {
    $initialize= $this->transport->call('initialize', [
      'protocolVersion' => $this->version,
      'clientInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
      'capabilities'    => [
        'roots'    => ['listChanged' => true],
        'sampling' => (object)[],
      ],
    ]);

    // After successful initialization, the client MUST send an initialized
    // notification to indicate it is ready to begin normal operations.
    $this->transport->notify('notifications/initialized');
    return $initialize->first();
  }

  /**
   * Calls a method
   *
   * @param  string $method
   * @param  ?[:var] $params
   * @return var
   */
  public function call($method, $params= null) {
    $this->server??= $this->initialize();
    return $this->transport->call($method, $params);
  }

  /** @return void */
  public function close() {
    $this->transport->close();
    $this->server= null;
  }
}