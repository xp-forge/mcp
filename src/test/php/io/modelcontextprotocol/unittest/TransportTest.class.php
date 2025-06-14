<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Transport, StdIo, StreamableHttp};
use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};

class TransportTest {

  #[Test]
  public function stdio() {
    Assert::instance(StdIo::class, Transport::for(PHP_BINARY.' -v'));
  }

  #[Test]
  public function stdio_array() {
    Assert::instance(StdIo::class, Transport::for([PHP_BINARY, '-v']));
  }

  #[Test, Values(['http://localhost:8080', 'http://example.com', 'https://example.com'])]
  public function streamable_http($uri) {
    Assert::instance(StreamableHttp::class, Transport::for($uri));
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '/.+ is not executable/')]
  public function illegal_argument() {
    Transport::for('@this.is.not.an.executable.file@');
  }
}