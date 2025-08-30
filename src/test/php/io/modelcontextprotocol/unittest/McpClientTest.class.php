<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Capabilities, McpClient, Transport, StreamableHttp, Value};
use test\{Assert, Test};

class McpClientTest {
  const ENDPOINT= 'http://localhost';

  #[Test]
  public function default_version() {
    Assert::equals('2025-06-18', (new McpClient(self::ENDPOINT))->version());
  }

  #[Test]
  public function explicitely_set_version() {
    Assert::equals('2024-11-05', (new McpClient(self::ENDPOINT, '2024-11-05'))->version());
  }

  #[Test]
  public function http_transport() {
    Assert::instance(StreamableHttp::class, (new McpClient(self::ENDPOINT))->transport());
  }

  #[Test]
  public function default_capabilities() {
    Assert::equals(Capabilities::client(), (new McpClient(self::ENDPOINT))->capabilities());
  }

  #[Test]
  public function change_capabilities() {
    Assert::equals(Capabilities::client(false), (new McpClient(self::ENDPOINT))->capabilities()->sampling(false));
  }

  #[Test]
  public function initialize() {
    $capabilities= new Capabilities(['tools' => ['listChanged' => false]]);
    $transport= new class($capabilities) extends Transport {
      private $capabilities;
      public $sent= [];
      public $version;

      public function __construct($capabilities, $version= '2025-03-26') {
        $this->capabilities= $capabilities;
        $this->version= $version;
      }

      public function setTrace($cat) { }

      public function version($version) { }

      public function notify($method) {
        $this->sent[]= ['notify' => $method];
      }

      public function call($method, $params= null) {
        $this->sent[]= ['call' => $method, 'params' => $params];

        // See https://modelcontextprotocol.io/specification/2025-03-26/basic/lifecycle
        yield 'result' => new Value([
          'protocolVersion' => $this->version,
          'serverInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
          'capabilities'    => $this->capabilities->struct(),
        ]);
      }

      public function close() { }
    };
    $fixture= new McpClient($transport);

    Assert::equals(
      [
        'protocolVersion' => $transport->version,
        'serverInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
        'capabilities'    => $capabilities->struct(),
      ],
      $fixture->initialize()->value()
    );
    Assert::equals(
      [
        ['call' => 'initialize', 'params' => [
          'protocolVersion' => $fixture->version(),
          'clientInfo'      => ['name' => 'XP/MCP', 'version' => '1.0.0'],
          'capabilities'    => $fixture->capabilities()->struct(),
        ]],
        ['notify' => 'notifications/initialized']
      ],
      $transport->sent
    );
  }
}