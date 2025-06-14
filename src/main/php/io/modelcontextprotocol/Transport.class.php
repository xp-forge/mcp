<?php namespace io\modelcontextprotocol;

use lang\{Closeable, CommandLine, IllegalArgumentException};
use util\log\Traceable;

/** @test io.modelcontextprotocol.unittest.TransportTest */
abstract class Transport implements Closeable, Traceable {

  /**
   * Sends a notification
   *
   * @param  string $method
   * @return void
   * @throws io.modelcontextprotocol.CallFailed
   */
  public abstract function notify($method);

  /**
   * Calls a method
   *
   * @param  string $method
   * @param  ?[:string] $params
   * @return io.modelcontextprotocol.Result
   * @throws io.modelcontextprotocol.CallFailed
   */
  public abstract function call($method, $params= null);

  /**
   * Creates a transport instance for a given string
   *
   * @throws lang.IllegalArgumentException
   */
  public static function for(string $arg): self {
    static $cmd= null;

    if (strstr($arg, '://')) {
      return new StreamableHttp($arg);
    } else {
      $cmd??= CommandLine::forName(PHP_OS);

      $parsed= $cmd->parse($arg);
      foreach ($cmd->resolve(array_shift($parsed)) as $executable) {
        return new StdIo($executable, ...$parsed);
      }
    }

    throw new IllegalArgumentException('No transport for '.$arg);
  }

  /** Ensure close() is called */
  public function __destruct() {
    $this->close();
  }
}
