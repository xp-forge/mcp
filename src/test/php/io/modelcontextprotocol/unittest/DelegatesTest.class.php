<?php namespace io\modelcontextprotocol\unittest;

use test\{Assert, Expect, Test};
use util\NoSuchElementException;

abstract class DelegatesTest {

  /** @return io.modelcontextprotocol.server.Delegates */
  protected abstract function fixture();

  #[Test]
  public function tools() {
    Assert::equals(
      [[
        'name'        => 'greetings_repeat',
        'description' => 'Repeats a given greeting',
        'inputSchema' => [
          'type'       => 'object',
          'properties' => [
            'greeting' => ['type' => 'string'],
            'times'    => ['type' => 'number'],
          ],
          'required' => ['greeting', 'times'],
        ]
      ]],
      [...$this->fixture()->tools()]
    );
  }

  #[Test]
  public function prompts() {
    Assert::equals(
      [[
        'name'        => 'greetings_user',
        'description' => 'Greets users',
        'arguments' => [
          [
            'name'        => 'name',
            'description' => 'Whom to greet',
            'required'    => true,
            'schema'      => ['type' => 'string'],
          ],
          [
            'name'        => 'style',
            'description' => 'Style',
            'required'    => false,
            'schema'      => ['type' => 'string', 'enum' => ['casual', 'friendly']],
          ],
        ],
      ]],
      [...$this->fixture()->prompts()]
    );
  }

  #[Test]
  public function resources() {
    Assert::equals(
      [
        [
          'uri'         => 'greeting://default',
          'name'        => 'greetings_default',
          'description' => 'Default greeting',
          'mimeType'    => 'text/plain',
          'dynamic'     => false,
        ],
        [
          'uri'         => 'greeting://icon',
          'name'        => 'greetings_icon',
          'description' => 'Greeting icon',
          'mimeType'    => 'image/gif',
          'dynamic'     => true,
        ]
      ],
      [...$this->fixture()->resources(false)]
    );
  }

  #[Test]
  public function resource_templates() {
    Assert::equals(
      [[
        'uriTemplate' => 'greeting://user/{name}',
        'name'        => 'greetings_get',
        'description' => 'Dynamic greeting for a user',
        'mimeType'    => 'text/plain',
      ]],
      [...$this->fixture()->resources(true)]
    );
  }

  #[Test]
  public function read_text_resource() {
    Assert::equals(
      [['uri' => 'greeting://default', 'mimeType' => 'text/plain', 'text' => 'Hello']],
      $this->fixture()->read('greeting://default')
    );
  }

  #[Test]
  public function read_binary_resource() {
    Assert::equals(
      [['uri' => 'greeting://icon', 'mimeType' => 'image/gif', 'blob' => 'R0lGODkuLi4=']],
      $this->fixture()->read('greeting://icon')
    );
  }

  #[Test, Expect(NoSuchElementException::class)]
  public function read_non_existant_resource() {
    $this->fixture()->read('greeting://non-existant');
  }
}