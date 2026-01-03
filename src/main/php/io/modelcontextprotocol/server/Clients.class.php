<?php namespace io\modelcontextprotocol\server;

use lang\MethodNotImplementedException;

abstract class Clients {

  /**
   * Looks up a client by a given client ID
   *
   * @param  string $clientId
   * @return ?[:var]
   */
  public abstract function lookup(string $clientId): ?array;

  /**
   * Registers a client and returns it, including the generated client ID.
   * In this default implementation, raises an exception indicating clients
   * cannot be registered.
   *
   * @throws lang.MethodNotImplementedException
   */
  public function register(array $client): array {
    throw new MethodNotImplementedException('Cannot register clients', __FUNCTION__);
  }

  /** Verififies a client by a given ID and redirewct URI */
  public function verify(?string $clientId, ?string $redirectUri): bool {
    return
      isset($clientId, $redirectUri) &&
      ($client= $this->lookup($clientId)) &&
      in_array($redirectUri, $client['redirect_uris'])
    ;
  }
}