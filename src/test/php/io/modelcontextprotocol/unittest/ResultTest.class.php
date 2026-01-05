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
}