<?php namespace io\modelcontextprotocol\unittest;

trait JsonRpc {

  /**
   * Encodes a value as JSON-RPC 2.0 result.
   *
   * @param  var $value
   * @return string
   */
  private function result($value) {
    return json_encode(['jsonrpc' => '2.0', 'id' => '6100', 'result' => $value]);
  }
}