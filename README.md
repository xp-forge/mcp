Model Context Protocol
======================

[![Build status on GitHub](https://github.com/xp-forge/mcp/workflows/Tests/badge.svg)](https://github.com/xp-forge/mcp/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/mcp/version.svg)](https://packagist.org/packages/xp-forge/mcp)

Implements the [Model Context Protocol](https://modelcontextprotocol.io/) for the XP Framework. Supports connecting to MCP servers via HTTP and standard I/O.

Example
-------

```php
use io\modelcontextprotocol\McpClient;
use util\cmd\Console;

// Use streamable HTTP
$client= new McpClient('http://localhost:3001');

// Use standard I/O
$client= new McpClient(['docker', 'run', '--rm', '-i', 'mcp/time']);

$response= $client->call('tools/list');
Console::writeLine($response->first());
```

See also
--------
* https://github.com/modelcontextprotocol/servers
* https://modelcontextprotocol.io/specification/2025-03-26
* https://deadprogrammersociety.com/2025/03/calling-mcp-servers-the-hard-way.html