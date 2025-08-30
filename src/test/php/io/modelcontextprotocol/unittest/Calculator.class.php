<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\{Implementation, Tool, Param};

#[Implementation]
class Calculator {

  /** Adds two numbers */
  #[Tool]
  public function add(
    #[Param(type: 'number')]
    $lhs,
    #[Param(type: 'number')]
    $rhs
  ) {
    return $lhs + $rhs;
  }
}