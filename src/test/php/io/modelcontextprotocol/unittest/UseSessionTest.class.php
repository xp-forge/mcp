<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\UseSession;
use test\{Assert, Test};
use web\session\ForTesting;

class UseSessionTest {
  const USER= ['id' => 'test'];
  const ISSER= 'http://test';

  #[Test]
  public function can_create() {
    new UseSession(new ForTesting());
  }

  #[Test]
  public function non_existant_session() {
    $fixture= new UseSession(new ForTesting());
    Assert::null($fixture->use('any-id'));
  }

  #[Test]
  public function user_and_scopes_from_existing() {
    $sessions= new ForTesting();
    $session= $sessions->create();
    $session->register('user', self::USER);

    $fixture= new UseSession($sessions);
    $values= $fixture->use($session->id());

    Assert::equals(['user' => self::USER, 'scopes' => null], $values);
  }

  #[Test]
  public function issue() {
    $sessions= new ForTesting();
    $session= $sessions->create();
    $session->register('user', self::USER);

    $fixture= new UseSession($sessions);
    $token= $fixture->issue(self::ISSER, [], $session);

    Assert::equals(
      [
        'access_token' => $session->id(),
        'expires_in'   => $session->expires() - time(),
      ],
      $token
    );
  }

  #[Test]
  public function issue_scoped() {
    $sessions= new ForTesting();
    $session= $sessions->create();
    $session->register('user', self::USER);

    $fixture= new UseSession($sessions);
    $token= $fixture->issue(self::ISSER, ['scopes' => ['mcp:tools', 'mcp:resources']], $session);

    Assert::equals(
      [
        'access_token' => $session->id(),
        'expires_in'   => $session->expires() - time(),
        'scope'        => 'mcp:tools mcp:resources',
      ],
      $token
    );
  }
}