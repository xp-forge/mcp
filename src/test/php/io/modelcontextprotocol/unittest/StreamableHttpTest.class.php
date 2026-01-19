<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\StreamableHttp;
use lang\FormatException;
use test\{Assert, Expect, Test, Values};
use webservices\rest\TestEndpoint;

class StreamableHttpTest {
  use JsonRpc;

  private function newFixture(array &$sessions, string $base= '/mcp'): StreamableHttp {
    $routes= [
      "POST {$base}" => function($call) use(&$sessions) {

        // Create session on intialization, destroy when logout is called
        $headers= ['Content-Type' => 'application/json'];
        $method= $call->request()->payload()->value()['method'];
        switch ($method) {
          case 'initialize':
            $id= sizeof($sessions) + 1;
            $sessions[$id]= ['active' => true, 'calls' => [$method]];
            $headers[StreamableHttp::SESSION]= $id;
            return $call->respond(200, 'OK', $headers, $this->result(true));

          case '$logout':
            $id= $call->request()->header(StreamableHttp::SESSION);
            $sessions[$id]['active']= false;
            $sessions[$id]['calls'][]= $method;
            return $call->respond(200, 'OK', $headers, $this->result(true));

          default:
            $id= $call->request()->header(StreamableHttp::SESSION);
            if (!$sessions[$id]['active']) return $call->respond(404, 'Session terminated');

            $sessions[$id]['calls'][]= $method;
            return $call->respond(200, 'OK', $headers, $this->result(true));
        }
      },
      "DELETE {$base}" => function($call) use(&$sessions) {
        $id= $call->request()->header(StreamableHttp::SESSION);
        $sessions[$id]['active']= false;
        $sessions[$id]['calls'][]= '$close';
        return $call->respond(204, 'No Content', [], null);
      }
    ];
    return new StreamableHttp(new TestEndpoint($routes, $base));
  }

  #[Test, Values(['application/json', 'application/json; charset=utf-8'])]
  public function json($type) {
    $value= ['name' => 'test', 'version' => '1.0.0'];
    $fixture= new StreamableHttp(new TestEndpoint([
      '/' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => $type], $this->result($value))
    ]));

    Assert::equals($value, $fixture->call('test')->current()->value());
  }

  #[Test, Values(['text/event-stream', 'text/event-stream; charset=utf-8'])]
  public function event_stream($type) {
    $value= ['name' => 'test', 'version' => '1.0.0'];
    $fixture= new StreamableHttp(new TestEndpoint([
      '/' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => $type], implode("\n", [
        'event: message',
        'data: '.$this->result($value)
      ]))
    ]));

    Assert::equals(['name' => 'test', 'version' => '1.0.0'], $fixture->call('test')->current()->value());
  }

  #[Test]
  public function multiple_events() {
    $fixture= new StreamableHttp(new TestEndpoint([
      '/' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => 'text/event-stream'], implode("\n", [
        'event: message',
        'data: '.$this->result('one'),
        '',
        'event: message',
        'data: '.$this->result('two')
      ]))
    ]));
    $results= [];
    foreach ($fixture->call('test')->current() as $kind => $result) {
      $results[]= [$kind => $result->value()];
    }

    Assert::equals([['message' => 'one'], ['message' => 'two']], $results);
  }

  #[Test, Expect(class: FormatException::class, message: 'Unexpected content type "text/plain"')]
  public function unexpected_content_type() {
    $fixture= new StreamableHttp(new TestEndpoint([
      '/' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => 'text/plain'], 'Test')
    ]));
    $fixture->call('test')->next();
  }

  #[Test]
  public function http_errors() {
    $fixture= new StreamableHttp(new TestEndpoint([
      '/' => fn($call) => $call->respond(404, 'Not found', ['Content-Type' => 'text/plain'], 'Not found: /')
    ]));
    Assert::equals([404 => 'Not found: /'], iterator_to_array($fixture->call('test')));
  }

  #[Test, Values(['/mcp', '/services/v1/mcp'])]
  public function session_handling($base) {
    $sessions= [];
    $fixture= $this->newFixture($sessions, $base);

    $fixture->call('initialize')->next();
    $fixture->call('tools/list')->next();
    $fixture->close();

    Assert::equals(
      ['active' => false, 'calls' => ['initialize', 'tools/list', '$close']],
      $sessions[1]
    );
  }

  #[Test]
  public function session_termination() {
    $sessions= [];
    $fixture= $this->newFixture($sessions);

    $fixture->call('initialize')->next();
    $fixture->call('$logout')->next();

    Assert::equals('terminated', $fixture->call('tools/list')->key());
  }
}