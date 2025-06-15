<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\Capabilities;
use test\{Assert, Test};

class CapabilitiesTest {

  #[Test]
  public function can_create() {
    new Capabilities();
  }

  #[Test]
  public function client_struct() {
    Assert::equals(
      ['sampling' => (object)[], 'roots' => ['listChanged' => true]],
      Capabilities::client()->struct()
    );
  }

  #[Test]
  public function without_sampling() {
    Assert::equals(
      ['roots' => ['listChanged' => true]],
      Capabilities::client()->sampling(false)->struct()
    );
  }

  #[Test]
  public function setting() {
    Assert::equals(['listChanged' => true], Capabilities::client()->setting('roots'));
  }

  #[Test]
  public function setting_path() {
    Assert::true(Capabilities::client()->setting('roots.listChanged'));
  }

  #[Test]
  public function non_existant_setting() {
    Assert::null(Capabilities::client()->setting('tools'));
  }
}