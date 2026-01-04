Model Context Protocol change log
=================================

## ?.?.? / ????-??-??

* **Heads up:** Require `xp-forge/sessions` version 4.0+ - @thekid

## 0.8.2 / 2026-01-04

* Made compatible with `xp-forge/web-auth` version 7.0 - @thekid

## 0.8.1 / 2026-01-03

* Continue OAuth without creating duplicate sessions, see this comment:
  https://github.com/xp-forge/mcp/issues/11#issuecomment-3707245647
  (@thekid)

## 0.8.0 / 2026-01-03

* Added support for `oauth-protected-resource` metadata (RFC 9728)
  (@thekid)
* Added PHP 8.6 to the test matrix - @thekid
* Renamed *meta()* to *metadata()* in `OAuth2Gateway` for consistency
  with wording used in the specification and e.g. `WWW-Authenticate`.
  (@thekid)
* Merged PR #12: Add OAuth2 gateway to implement authorization for MCP
  (@thekid)

## 0.7.0 / 2025-12-31

* Fixed issue #10: Error: Expected string for description, by using a
  default value composed from the namespace and method name
  (@thekid)
* Merged PR #9: Add possibility to access request values via `#[Value]`
  (@thekid)

## 0.6.0 / 2025-08-31

* Merged PR #7: Support multiple delegates via `Delegates` class
  (@thekid)
* Added string representations for errors, results and event streams
  (@thekid)

## 0.5.0 / 2025-08-31

* Merged PR #6: Extract JSON RPC implementation into dedicated class
  (@thekid)
* Passed complete response body to `CallFailed` constructor to simplify
  debugging protocol errors
  (@thekid)
* Bumped default protocol version to `2025-06-18`, pass negotiated
  version via `MCP-Protocol-Version` when using HTTP.
  (@thekid)
* Fixed issue #5: Mounting MCP servers to subpaths. Accomplished by
  delegating this to the routing mechanism
  (@thekid)

## 0.4.0 / 2025-08-30

* Merged PR #4: Implement MCP server API. This adds support for adding an
  MCP endpoint into an https://github.com/xp-forge/web application.
  (@thekid)

## 0.3.0 / 2025-08-23

* Merged PR #3: Return an *Authorization* instance from `initialize()`
  (@thekid)
* Handle session termination by server, starting a new session by sending
  and initialize request
  (@thekid)
* Changed `initialize()` to return a *Result* instance instead of raising
  an error, adding the ability to handle missing authentication gracefully
  (@thekid)
* Call `DELETE` on the MCP endpoint if an MCP session ID is established
  (@thekid)

## 0.2.0 / 2025-07-29

* Fixed issue #1: Standard I/O default line limit (8192 bytes) exceeded
  (@thekid)
* Renamed *first()* -> *value()* to more accurately match what is being
  returned
  (@thekid)

## 0.1.0 / 2025-06-14

* Hello World! First release - @thekid