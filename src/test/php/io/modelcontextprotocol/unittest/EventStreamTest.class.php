<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{EventStream, Result};
use io\streams\MemoryInputStream;
use test\{Assert, Test};

class EventStreamTest {

  #[Test]
  public function can_create() {
    new EventStream(new MemoryInputStream(''));
  }

  #[Test]
  public function data_only() {
    $stream= new EventStream(new MemoryInputStream(<<<EVENT_STREAM
      data: {"jsonrpc": "2.0", "id": "6100", "result": "Test"}
      EVENT_STREAM
    ));
    Assert::equals(['' => new Result('Test')], iterator_to_array($stream));
  }

  #[Test]
  public function event_with_data() {
    $stream= new EventStream(new MemoryInputStream(<<<EVENT_STREAM
      event: message
      data: {"jsonrpc": "2.0", "id": "6100", "result": "Test"}
      EVENT_STREAM
    ));
    Assert::equals(['message' => new Result('Test')], iterator_to_array($stream));
  }

  #[Test]
  public function json_deserialization() {
    $stream= new EventStream(new MemoryInputStream(<<<EVENT_STREAM
      data: {"jsonrpc": "2.0", "id": "6100", "result": {}}
      EVENT_STREAM
    ));
    Assert::equals(['' => new Result((object)[])], iterator_to_array($stream));
  }
}