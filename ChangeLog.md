Model Context Protocol change log
=================================

## ?.?.? / ????-??-??

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