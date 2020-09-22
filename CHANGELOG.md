# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed

- No changes yet.

## [0.1.0] (road to mvp) - 2020-09-22
### Added

- Asynchronous web server setup for API requests using native PHP environment (based on
[ReactPHP Foundation](https://github.com/itnelo/reactphp-foundation) skeleton).
- Stub for the `HardwareMarkHandler` that emits a list with CPU prices and benchmark values.
- Deferred event dispatcher to handle application-level events using the centralized event loop
(as a bridge to the Symfony's [event dispatcher](https://github.com/symfony/event-dispatcher);
candidate for extracting into the separate package).
- Compiler pass for DI container to provide registration of `Evenement` listeners* in the centralized place
(candidate for extracting).
- Reactive UI w/ Svelte and Spectre.css assets to visualize data from the API handlers
(including client-side page routing, deserialization and translations).
- Front: contracts for the domain entities (`Cpu.js`, `Benchmark.js`, `Price.js`) using both ES5 and ES6 
class notation.
- Front: components for data receiving and entity representation (`Cpu\Fetcher.js`, `Cpu\Viewer.svelte`, 
`Representation\Table.svelte`).
- Configuration for application containers (based on `php-cli` docker image).
- Configuration for single-process & single-threaded [Lighttpd](https://lighttpd.net) containers to serve compiled 
frontend files.
- [HAProxy](https://www.haproxy.com) configuration for routing and load balancing.
- `.travis.yml` for [CI checks](https://travis-ci.com/github/sterlett/sterlett).
- `bin/configure-env` script to manage environments.

\* — [igorw/evenement](https://github.com/igorw/evenement), the way how ReactPHP components communicate with each other.

[Unreleased]: https://github.com/sterlett/sterlett/compare/0.1.0...0.x
[0.2.0]: https://github.com/sterlett/sterlett/compare/0.1.0..0.2.0
[0.1.0]: https://github.com/sterlett/sterlett/releases/tag/0.1.0