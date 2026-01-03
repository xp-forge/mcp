Model Context Protocol
======================

[![Build status on GitHub](https://github.com/xp-forge/mcp/workflows/Tests/badge.svg)](https://github.com/xp-forge/mcp/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/mcp/version.svg)](https://packagist.org/packages/xp-forge/mcp)

Implements the [Model Context Protocol](https://modelcontextprotocol.io/) for the XP Framework.

Client
------
Connecting to an MCP server:

```php
use io\modelcontextprotocol\McpClient;
use util\cmd\Console;

// Use streamable HTTP
$client= new McpClient('http://localhost:3001');

// Use standard I/O
$client= new McpClient(['docker', 'run', '--rm', '-i', 'mcp/time']);

$response= $client->call('tools/list');
Console::writeLine($response->value());
```

Server
------
Uses the [xp-forge/web](https://github.com/xp-forge/web) library:

```php
use io\modelcontextprotocol\McpServer;
use io\modelcontextprotocol\server\{Tool, Param};
use web\Application;

class Test extends Application {

  public function routes() {
    return new McpServer([
      'greeting' => new class() {

        /** Sends a greeting */
        #[Tool]
        public function greet(#[Param('Whom to greet')] $name= null) {
          return 'Hello, '.($name ?? 'unknown user');
        }
      }
    ]);
  }
}
```

Run this via `xp -supervise web Test`.

Organizing code
---------------
MCP tools, resources and prompts may be organized into classes as follows:

```php
namespace com\example\api;

use io\modelcontextprotocol\server\{Resource, Prompt, Tool, Param, Implementation};

#[Implementation]
class Greeting {

  /** Dynamic greeting for a user */
  #[Resource('greeting://user/{name}')]
  public function get($name) {
    return "Hello {$name}";
  }

  /** Greets users */
  #[Prompt]
  public function user(
    #[Param('Whom to greet')] $name,
    #[Param(type: ['type' => 'string', 'enum' => ['casual', 'friendly']])] $style= 'casual'
  ) {
    return "Write a {$style} greeting for {$name}";
  }

  /** Sends a given greeting by email */
  #[Tool]
  public function send(
    #[Param('Recipient email address')] $recipient,
    #[Param('The text to send')] $greeting
  ) {
    // TBI
  }
}}
```

The web application then becomes this:

```php
use io\modelcontextprotocol\McpServer;
use web\Application;

class Test extends Application {

  public function routes() {
    return new McpServer('com.example.api');
  }
}
```

Authentication
--------------
To add OAuth2 authentication to your server, use the following:

```php
use io\modelcontextprotocol\McpServer;
use io\modelcontextprotocol\server\{ImplementationsIn, OAuth2Gateway, Clients, UseSession};
use web\Application;
use web\session\InFileSystem;

class Test extends Application {

  public function routes() {
    $clients= new class() extends Clients { /* TBI */ };

    $sessions= (new InFileSystem())->named('oauth');
    $gateway= new OAuth2Gateway('/oauth', $clients, new UseSession($sessions));

    $auth= /* Some class extending web.auth.Authentication */;

    $server= new McpServer(new ImplementationsIn('impl'));
    return [
      '/mcp'   => $gateway->authenticate($server),
      '/oauth' => $gateway->flow($auth, $sessions),
      '/.well-known/oauth-protected-resource'   => $gateway->resource(),
      '/.well-known/oauth-authorization-server' => $gateway->metadata(),
    ];
  }
}
```

See also
--------
* https://github.com/modelcontextprotocol/servers
* https://modelcontextprotocol.io/docs/learn/server-concepts
* https://modelcontextprotocol.io/specification/2025-06-18
* https://deadprogrammersociety.com/2025/03/calling-mcp-servers-the-hard-way.html
* https://blog.christianposta.com/understanding-mcp-authorization-with-dynamic-client-registration/