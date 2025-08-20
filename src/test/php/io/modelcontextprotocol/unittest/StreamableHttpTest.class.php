<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\{StreamableHttp, CallFailed};
use lang\FormatException;
use test\{Assert, Expect, Test, Values};
use webservices\rest\TestEndpoint;

class StreamableHttpTest {
  use JsonRpc;

  #[Test, Values(['application/json', 'application/json; charset=utf-8'])]
  public function json($type) {
    $value= ['name' => 'test', 'version' => '1.0.0'];
    $fixture= new StreamableHttp(new TestEndpoint([
      '/mcp' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => $type], $this->result($value))
    ]));

    Assert::equals($value, $fixture->call('test')->value());
  }

  #[Test, Values(['text/event-stream', 'text/event-stream; charset=utf-8'])]
  public function event_stream($type) {
    $value= ['name' => 'test', 'version' => '1.0.0'];
    $fixture= new StreamableHttp(new TestEndpoint([
      '/mcp' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => $type], implode("\n", [
        'event: message',
        'data: '.$this->result($value)
      ]))
    ]));

    Assert::equals(['name' => 'test', 'version' => '1.0.0'], $fixture->call('test')->value());
  }

  #[Test]
  public function multiple_events() {
    $fixture= new StreamableHttp(new TestEndpoint([
      '/mcp' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => 'text/event-stream'], implode("\n", [
        'event: message',
        'data: '.$this->result('one'),
        '',
        'event: message',
        'data: '.$this->result('two')
      ]))
    ]));
    $results= [];
    foreach ($fixture->call('test') as $kind => $result) {
      $results[]= [$kind => $result->value()];
    }

    Assert::equals([['message' => 'one'], ['message' => 'two']], $results);
  }

  #[Test, Expect(class: FormatException::class, message: 'Unexpected content type "text/plain"')]
  public function unexpected_content_type() {
    $fixture= new StreamableHttp(new TestEndpoint([
      '/mcp' => fn($call) => $call->respond(200, 'OK', ['Content-Type' => 'text/plain'], 'Test')
    ]));
    $fixture->call('test');
  }

  #[Test, Expect(class: CallFailed::class, message: '#404: Not found: /mcp')]
  public function http_errors() {
    $fixture= new StreamableHttp(new TestEndpoint([
      '/mcp' => fn($call) => $call->respond(404, 'Not found', ['Content-Type' => 'text/plain'], 'Not found: /mcp')
    ]));
    $fixture->call('test');
  }

  #[Test]
  public function session_handling() {
    $sessions= [];
    $fixture= new StreamableHttp(new TestEndpoint([
      'POST /mcp' => function($call) use(&$sessions) {
        $headers= ['Content-Type' => 'application/json'];

        // Start session
        if (null === ($session= $call->request()->header('Mcp-Session-Id'))) {
          $headers['Mcp-Session-Id']= '6100';
        }

        $sessions['call:'.$call->request()->payload()->value()['method']]= $session;
        return $call->respond(200, 'OK', $headers, $this->result(true));
      },
      'DELETE /mcp' => function($call) use(&$sessions) {
        $sessions['close']= $call->request()->header('Mcp-Session-Id');
        return $call->respond(204, 'No Content', [], null);
      }
    ]));

    $fixture->call('initialize');
    $fixture->call('tools/list');
    $fixture->close();

    Assert::equals(['call:initialize' => null, 'call:tools/list' => '6100', 'close' => '6100'], $sessions);
  }
}