Model Context Protocol change log
=================================

## ?.?.? / ????-??-??

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