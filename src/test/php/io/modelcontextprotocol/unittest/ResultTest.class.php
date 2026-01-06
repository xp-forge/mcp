<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\Result;
use test\{Assert, Test};

class ResultTest {
  const OBJECT= ['temperature' => 22.5, 'conditions' => 'Partly cloudy', 'humidity' => 65];

  #[Test]
  public function success() {
    Assert::equals(['content' => []], Result::success()->struct());
  }

  #[Test]
  public function success_with_text() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'It worked']]],
      Result::success('It worked')->struct()
    );
  }

  #[Test]
  public function success_with_object() {
    Assert::equals(
      ['structuredContent' => self::OBJECT],
      Result::success(self::OBJECT)->struct()
    );
  }

  #[Test]
  public function error() {
    Assert::equals(['content' => [], 'isError' => true], Result::error()->struct());
  }

  #[Test]
  public function error_with_text() {
    Assert::equals(
      ['content' => [['type' => 'text', 'text' => 'Error 404']], 'isError' => true],
      Result::error('Error 404')->struct()
    );
  }

  #[Test]
  public function error_with_object() {
    Assert::equals(
      ['structuredContent' => self::OBJECT, 'isError' => true],
      Result::error(self::OBJECT)->struct()
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
          'text'        => $code,
          'mimeType'    => 'text/x-rust',
        ],
      ]]],
      Result::success()->resource('file:///project/src/main.rs', $code, 'text/x-rust')->struct()
    );
  }

  #[Test]
  public function structured() {
    Assert::equals(
      [
        'structuredContent' => self::OBJECT,
        'content'           => [['type' => 'text', 'text' => json_encode(self::OBJECT)]],
      ],
      Result::structured(self::OBJECT)->struct()
    );
  }

  #[Test]
  public function structured_with_text() {
    $text= 'Temperature: 22.5°, partly cloudy with a humidity of 65';
    Assert::equals(
      [
        'structuredContent' => self::OBJECT,
        'content'           => [['type' => 'text', 'text' => $text]],
      ],
      Result::structured(self::OBJECT, $text)->struct()
    );
  }

  #[Test]
  public function structured_with_iterable() {
    $text= ['Temperature: 22.5°', 'Conditions: partly cloudy', 'Humidity: 65'];
    Assert::equals(
      [
        'content'           => [
          ['type' => 'text', 'text' => $text[0]],
          ['type' => 'text', 'text' => $text[1]],
          ['type' => 'text', 'text' => $text[2]],
        ],
        'structuredContent' => self::OBJECT,
      ],
      Result::structured(self::OBJECT, $text)->struct()
    );
  }

  #[Test]
  public function structured_with_error() {
    $error= ['code' => 'INVALID_DEPARTURE_DATE', 'message' => 'Departure date must be in the future'];
    Assert::equals(
      [
        'structuredContent' => ['error' => $error],
        'content'           => [['type' => 'text', 'text' => $error['message']]],
        'isError'           => true,
      ],
      Result::structured(['error' => $error], $error['message'], true)->struct()
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
    Assert::equals(
      ['structuredContent' => self::OBJECT],
      Result::success()->cast(self::OBJECT)->struct()
    );
  }
}