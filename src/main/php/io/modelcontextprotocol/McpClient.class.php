<?php namespace io\modelcontextprotocol;

use util\log\Traceable;

/**
 * Model Context Protocol client
 * 
 * @see  https://deadprogrammersociety.com/2025/03/calling-mcp-servers-the-hard-way.html
 */
class McpClient implements Traceable {
  private $transport, $version;
  private $server= null;

  public function __construct($endpoint, $version= '2025-03-26') {
    $this->transport= Transport::for($endpoint);
    $this->version= $version;
  }

  /** @param ?util.log.LogCategory */
  public function setTrace($cat) {
    $this->transport->setTrace($cat);
  }

  public function initialize() {
    $initialize= $this->transport->call('initialize', [
      'protocolVersion' => $this->version,
      'clientInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
      'capabilities'    => [
        'roots'    => ['listChanged' => true],
        'sampling' => (object)[],
      ],
    ]);
    $this->server= $initialize->first();
    $this->transport->notify('notifications/initialized');
  }

  /**
   * Calls a method
   *
   * @param  string $method
   * @param  ?[:var] $params
   * @return var
   */
  public function call($method, $params= null) {

    // The initialization phase MUST be the first interaction between client and server
    // After successful initialization, the client MUST send an initialized notification
    // to indicate it is ready to begin normal operations
    $this->server??= $this->initialize();

    return $this->transport->call($method, $params);
  }

  /** @return void */
  public function close() {
    $this->transport->close();
  }
}