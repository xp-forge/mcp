<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\Result;
use test\{Assert, Test};

class ResultTest {

  #[Test]
  public function success() {
    Assert::equals(['content' => []], Result::success()->struct());
  }

  #[Test]
  public function error() {
    Assert::equals(['content' => [], 'isError' => true], Result::error()->struct());
  }

  #[Test]
  public function success_with_text() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'It worked']]],
      Result::success('It worked')->struct()
    );
  }

  #[Test]
  public function error_with_text() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'Error 404']], 'isError' => true],
      Result::error('Error 404')->struct()
    );
  }

  #[Test]
  public function add() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'Test']]],
      Result::success()->add('text', ['text' => 'Test'])->struct()
    );
  }

  #[Test]
  public function annotations() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'Test','annotations' => ['audience' => ['user']]]]],
      Result::success()->add('text', ['text' => 'Test'], ['audience' => ['user']])->struct()
    );
  }

  #[Test]
  public function with_text() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'Tool result text']]],
      Result::success()->text('Tool result text')->struct()
    );
  }

  #[Test]
  public function with_image() {
    Assert::equals(
      ['content' => [['type' => 'image', 'data' => 'R0lGODlhLi4u', 'mimeType' => 'image/gif']]],
      Result::success()->image('GIF89a...', 'image/gif')->struct()
    );
  }

  #[Test]
  public function with_audio() {
    Assert::equals(
      ['content' => [['type' => 'audio', 'data' => 'UklGRi4uLg==', 'mimeType' => 'audio/wav']]],
      Result::success()->audio('RIFF...', 'audio/wav')->struct()
    );
  }

  #[Test]
  public function with_resource_link() {
    Assert::equals(
      ['content' => [[
        'type'        => 'resource_link',
        'uri'         => 'file:///project/src/main.rs',
        'name'        => 'main.rs',
        'description' => 'Main',
        'mimeType'    => 'text/x-rust',
      ]]],
      Result::success()->link('file:///project/src/main.rs', 'main.rs', 'Main', 'text/x-rust')->struct()
    );
  }

  #[Test]
  public function with_embedded_resource() {
    $code= "fn main() {\n    println!(\"Hello world!\");\n}";
    Assert::equals(
      ['content' => [[
        'type'        => 'resource',
        'resource'    => [
          'uri'         => 'file:///project/src/main.rs',
          'mimeType'    => 'text/x-rust',
          'text'        => $code,
        ],
      ]]],
      Result::success()->resource('file:///project/src/main.rs', 'text/x-rust', $code)->struct()
    );
  }

  #[Test]
  public function structured() {
    $object= ['temperature' => 22.5, 'conditions' => 'Partly cloudy', 'humidity' => 65];
    Assert::equals(
      [
        'structuredContent' => $object,
        'content'           => [['type' => 'text', 'text' => json_encode($object)]],
      ],
      Result::structured($object)->struct()
    );
  }

  #[Test]
  public function cast_scalar() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'Test']]],
      Result::success()->cast('Test')->struct()
    );
  }

  #[Test]
  public function cast_object() {
    $object= ['temperature' => 22.5, 'conditions' => 'Partly cloudy', 'humidity' => 65];
    Assert::equals(
      [
        'structuredContent' => $object,
        'content'           => [['type' => 'text', 'text' => json_encode($object)]],
      ],
      Result::success()->cast($object)->struct()
    );
  }
}