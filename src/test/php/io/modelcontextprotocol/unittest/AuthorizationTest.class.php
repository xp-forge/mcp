<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{Authorization, CallFailed};
use lang\FormatException;
use test\{Assert, Expect, Test};

class AuthorizationTest {
  const PARAMETERS= [
    'error'             => 'invalid_request',
    'resource_metadata' => 'https://example.com/.well-known/oauth-protected-resource/mcp',
  ];

  #[Test]
  public function can_create() {
    new Authorization('Bearer', self::PARAMETERS);
  }

  #[Test]
  public function scheme() {
    Assert::equals('Bearer', (new Authorization('Bearer', self::PARAMETERS))->scheme);
  }

  #[Test]
  public function parameters() {
    Assert::equals(
      self::PARAMETERS,
      (new Authorization('Bearer', self::PARAMETERS))->parameters
    );
  }

  #[Test]
  public function header() {
    Assert::equals('Bearer', (new Authorization('Bearer', []))->header());
  }

  #[Test]
  public function parse() {
    Assert::equals((new Authorization('Bearer', [])), Authorization::parse('Bearer'));
  }

  #[Test]
  public function header_parameters() {
    Assert::equals(
      'Bearer error="invalid_request", resource_metadata="https://example.com/.well-known/oauth-protected-resource/mcp"',
      (new Authorization('Bearer', self::PARAMETERS))->header()
    );
  }

  #[Test]
  public function parse_parameters() {
    Assert::equals(
      (new Authorization('Bearer', self::PARAMETERS)),
      Authorization::parse('Bearer error="invalid_request", resource_metadata="https://example.com/.well-known/oauth-protected-resource/mcp"')
    );
  }

  #[Test, Expect(class: FormatException::class, message: 'Unclosed string in parameter "error"')]
  public function parse_unclosed() {
    Authorization::parse('Bearer error="unclosed');
  }

  #[Test, Expect(class: CallFailed::class, message: '#401: Bearer error="invalid_request", resource_metadata="https://example.com/.well-known/oauth-protected-resource/mcp"')]
  public function value() {
    (new Authorization('Bearer', self::PARAMETERS))->value();
  }
}