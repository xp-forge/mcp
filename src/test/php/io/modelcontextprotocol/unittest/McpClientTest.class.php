<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\McpClient;
use test\{Assert, Test};

class McpClientTest {

  #[Test]
  public function http() {
    new McpClient('http://localhost');
  }
}
