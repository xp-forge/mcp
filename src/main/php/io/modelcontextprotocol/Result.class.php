<?php namespace io\modelcontextprotocol;

use IteratorAggregate;
use lang\FormatException;
use util\Objects;

/** @test io.modelcontextprotocol.unittest.ResultTest */
abstract class Result implements IteratorAggregate {

  /** @return var */
  public abstract function value();

  /**
   * Creates a result from a JSON-RPC message, which is either a `Value` or an `Error`.
   *
   * @param  [:var] $message
   * @return self
   * @throws lang.FormatException
   */
  public static function from(array $message) {
    if ($result= $message['result'] ?? null) {
      return new Value($result);
    } else if ($error= $message['error'] ?? null) {
      return new Error($error['code'], $error['message']);
    }

    throw new FormatException('Expected result or error, have '.Objects::stringOf($message));
  }
}