<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{McpClient, Transport, StreamableHttp, Value};
use test\{Assert, Test};

class McpClientTest {

  #[Test]
  public function default_version() {
    Assert::equals('2025-03-26', (new McpClient('http://localhost'))->version());
  }

  #[Test]
  public function explicitely_set_version() {
    Assert::equals('2025-06-14', (new McpClient('http://localhost', '2025-06-14'))->version());
  }

  #[Test]
  public function http_transport() {
    Assert::instance(StreamableHttp::class, (new McpClient('http://localhost'))->transport());
  }

  #[Test]
  public function initialize() {
    $fixture= new McpClient(new class() extends Transport {
      public function setTrace($cat) { }

      public function notify($method) { }

      public function call($method, $params= null) {
        return new Value(['result' => 'test']);
      }

      public function close() { }
    });
    Assert::equals('test', $fixture->initialize());
  }
}