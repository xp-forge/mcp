<?php namespace io\modelcontextprotocol;

use IteratorAggregate;

abstract class Result implements IteratorAggregate {

  /** @return var */
  public abstract function first();
}