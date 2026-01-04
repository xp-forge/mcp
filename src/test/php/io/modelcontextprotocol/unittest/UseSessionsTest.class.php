<?php namespace io\modelcontextprotocol\unittest;

use io\modelcontextprotocol\server\UseSessions;
use test\{Assert, Test};
use web\session\ForTesting;

class UseSessionsTest {
  const USER= ['id' => 'test'];
  const ISSER= 'http://test';

  #[Test]
  public function can_create() {
    new UseSessions(new ForTesting());
  }

  #[Test]
  public function non_existant_session() {
    $fixture= new UseSessions(new ForTesting());
    Assert::null($fixture->use('any-id'));
  }

  #[Test]
  public function user_and_scopes_from_existing() {
    $sessions= new ForTesting();
    $session= $sessions->create();
    $session->register('user', self::USER);

    $fixture= new UseSessions($sessions);
    $values= $fixture->use($session->id());

    Assert::equals(['user' => self::USER, 'scopes' => null], $values);
  }

  #[Test]
  public function issue() {
    $sessions= new ForTesting();
    $auth= $sessions->create();
    $auth->register('user', self::USER);

    $fixture= new UseSessions($sessions);
    $token= $fixture->issue(self::ISSER, [], $auth->value('user'));
    $session= $sessions->open($token['access_token']);

    Assert::equals(self::USER, $session->value('user'));
    Assert::equals(
      [
        'access_token' => $session->id(),
        'expires_in'   => $sessions->duration(),
      ],
      $token
    );
  }

  #[Test]
  public function issue_scoped() {
    $sessions= new ForTesting();
    $auth= $sessions->create();
    $auth->register('user', self::USER);

    $fixture= new UseSessions($sessions);
    $token= $fixture->issue(self::ISSER, ['scopes' => ['mcp:tools', 'mcp:resources']], $auth->value('user'));
    $session= $sessions->open($token['access_token']);

    Assert::equals(self::USER, $session->value('user'));
    Assert::equals(
      [
        'access_token' => $session->id(),
        'expires_in'   => $sessions->duration(),
        'scope'        => 'mcp:tools mcp:resources',
      ],
      $token
    );
  }
}