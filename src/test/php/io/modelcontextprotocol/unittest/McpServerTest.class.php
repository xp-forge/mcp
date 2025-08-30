<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Capabilities, McpServer};
use test\{Assert, Test};

class McpServerTest {

  #[Test]
  public function default_version() {
    Assert::equals('2025-03-26', (new McpServer(new class() { }))->version());
  }

  #[Test]
  public function default_capabilities() {
    Assert::equals(Capabilities::server(), (new McpServer(new class() { }))->capabilities());
  }
}