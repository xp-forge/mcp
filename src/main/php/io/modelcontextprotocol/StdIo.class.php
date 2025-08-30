<?php namespace io\modelcontextprotocol;

use lang\{Process, IllegalStateException};

/**
 * Standard I/O transport
 * 
 * @see  https://modelcontextprotocol.io/specification/2025-03-26/basic/transports#stdio
 */
class StdIo extends Transport {
  private $process;
  private $cat= null;
  private $buffer= '';

  /**
   * Creates a new transport instance
   *
   * @param  string|lang.Process $command
   * @param  string[] $args
   */
  public function __construct($command, array $args= []) {
    $this->process= $command instanceof Process ? $command : new Process(
      $command,
      $args,
      null,
      null,
      [['pipe', 'r'], ['pipe', 'w'], STDERR]
    );
  }

  /** @param ?util.log.LogCategory */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  /** @param string $version */ 
  public function version($version) {
    // Not applicable for this implementation
  }

  /**
   * Sends the given payload as JSON-RPC 2.0 message to the process' standard input.
   *
   * @param  [:var] $payload
   * @return void
   */
  private function send($payload) {
    $request= json_encode(['jsonrpc' => '2.0'] + $payload);
    $this->cat && $this->cat->debug('>>>', $request);
    $this->process->in->writeLine($request);
  }

  /**
   * Sends a notification
   *
   * @param  string $method
   * @return void
   * @throws io.modelcontextprotocol.CallFailed
   */
  public function notify($method) {
    $this->send(['method' => $method]);
  }

  /**
   * Calls a method
   *
   * @param  string $method
   * @param  ?[:string] $params
   * @return iterable
   * @throws lang.IllegalStateException
   */
  public function call($method, $params= null) {
    $this->send(['id' => uniqid(), 'method' => $method, 'params' => $params ?: (object)[]]);

    while (false === ($p= strpos($this->buffer, "\n"))) {
      if (false === ($chunk= $this->process->out->read())) {
        $this->process->close();
        throw new IllegalStateException('Unexpected EOF from process');
      }
      $this->buffer.= $chunk;
    }

    $response= substr($this->buffer, 0, $p);
    $this->buffer= substr($this->buffer, $p);
    $this->cat && $this->cat->debug('<<<', $response);

    yield 'result' => Result::from(json_decode($response, true));
  }

  /** @return void */
  public function close() {
    if ($this->process->running()) {
      $this->process->close();
    }
  }
}