<?php namespace io\modelcontextprotocol;

use util\log\Traceable;

/**
 * Model Context Protocol client
 * 
 * @see  https://deadprogrammersociety.com/2025/03/calling-mcp-servers-the-hard-way.html
 * @test io.modelcontextprotocol.unittest.McpClientTest
 */
class McpClient implements Traceable {
  private $transport, $version, $capabilities;
  private $server= null;

  /**
   * Creates a new MCP client with a given endpoint and protocol version
   *
   * @param  string|array|util.URI|io.modelcontextprotocol.Transport $endpoint
   * @param  string $version
   */
  public function __construct($endpoint, string $version= '2025-06-18') {
    $this->transport= $endpoint instanceof Transport ? $endpoint : Transport::for($endpoint);
    $this->version= $version;
    $this->capabilities= Capabilities::client();
  }

  /** @return io.modelcontextprotocol.Transport */
  public function transport() { return $this->transport; }

  /** @return string */
  public function version() { return $this->version; }

  /** @return io.modelcontextprotocol.Capabilities */
  public function capabilities() { return $this->capabilities; }

  /** @param ?util.log.LogCategory */
  public function setTrace($cat) {
    $this->transport->setTrace($cat);
  }

  /**
   * The initialization phase MUST be the first interaction between client and server
   *
   * @see    https://modelcontextprotocol.io/specification/2025-03-26/basic/lifecycle#lifecycle-phases
   * @throws io.modelcontextprotocol.CallFailed
   */
  public function initialize(): Outcome {
    $init= $this->transport->call('initialize', [
      'protocolVersion' => $this->version,
      'clientInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
      'capabilities'    => $this->capabilities->struct(),
    ]);

    // After successful initialization, the client MUST send an initialized
    // notification to indicate it is ready to begin normal operations.
    switch ($init->key()) {
      case 'result':
        $result= $init->current();
        $this->server= $result->value();
        $this->transport->version($this->server['protocolVersion'] ?? $this->version);
        $this->transport->notify('notifications/initialized');
        return $result;

      case 'authorize':
        return Authorization::parse($init->current());

      default:
        return $init->throw(new CallFailed($init->key(), $init->current()));
    }
  }

  /**
   * Calls a method
   *
   * @param  string $method
   * @param  ?[:var] $params
   * @throws io.modelcontextprotocol.CallFailed
   */
  public function call($method, $params= null): Outcome {
    $this->server ?? $this->initialize()->value();
    foreach ($this->transport->call($method, $params) as $op => $result) {
      switch ($op) {
        case 'result': return $result;
        case 'terminated': $this->server= $this->initialize()->value(); break;
        default: throw new CallFailed($op, $result);
      }
    }
  }

  /** @return void */
  public function close() {
    $this->transport->close();
    $this->server= null;
  }
}