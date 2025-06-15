<?php namespace io\modelcontextprotocol;

use lang\Process;

/**
 * Standard I/O transport
 * 
 * @see  https://modelcontextprotocol.io/specification/2025-03-26/basic/transports#stdio
 */
class StdIo extends Transport {
  private $process;
  private $cat= null;

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
   * @return io.modelcontextprotocol.Result
   * @throws io.modelcontextprotocol.CallFailed
   */
  public function call($method, $params= null) {
    $this->send(['id' => uniqid(), 'method' => $method, 'params' => $params ?: (object)[]]);

    $response= $this->process->out->readLine();
    $this->cat && $this->cat->debug('<<<', $response);
    if (false === $response) {
      $this->process->close();
      throw new CallFailed(-1, 'Unexpected EOF from process');
    }

    return Result::from(json_decode($response, true));
  }

  /** @return void */
  public function close() {
    if ($this->process->running()) {
      $this->process->close();
    }
  }
}