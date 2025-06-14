<?php namespace io\modelcontextprotocol;

use peer\ProtocolException;

class CallFailed extends ProtocolException {

  public function __construct($code, $message, $cause= null) {
    parent::__construct('#'.$code.': '.$message, $cause);
  }
}