<?php namespace io\modelcontextprotocol;

use lang\{Closeable, CommandLine, IllegalArgumentException};
use util\URI;
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
   * Resolves a command line
   *
   * @param  lang.CommandLine $cmd
   * @param  string[] $args
   * @return io.modelcontextprotocol.StdIo
   * @throws lang.IllegalArgumentException
   */
  private static function resolve($cmd, $args) {
    $file= array_shift($args);
    foreach ($cmd->resolve($file) as $executable) {
      return new StdIo($executable, ...$args);
    }

    throw new IllegalArgumentException($file.' is not executable');
  }

  /**
   * Creates a transport instance for a given string
   *
   * @param  string|array|util.URI $arg
   * @throws lang.IllegalArgumentException
   */
  public static function for($arg): self {
    static $cmd= null;

    if (is_array($arg)) {
      return self::resolve($cmd??= CommandLine::forName(PHP_OS), $arg);
    } else if (!strstr($arg, '://')) {
      return self::resolve($cmd??= CommandLine::forName(PHP_OS), $cmd->parse($arg));
    } else {
      return new StreamableHttp($arg);
    }
  }

  /** Ensure close() is called */
  public function __destruct() {
    $this->close();
  }
}
