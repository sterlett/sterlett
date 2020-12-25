# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed

- No description yet.

## [0.2.0] (mvp) - 2020-12-25

### Added

- Console environment setup (based on [symfony/console](https://github.com/symfony/console)).
- ReactPHP Bridge: manual buffering for HTTP responses (to track data retrieving progress).
- Interfaces to glue application components from the different layers: `BenchmarkInterface`, `PriceInterface` and
async `ProviderInterface` (with its blocking counterpart) for both benchmark & price retrieving scopes.
- `Progress\TrackerInterface`, middleware for the async HTTP client and adapter for Symfony's progress bar to track
activity of application components.
- Parser (and related components) for the PassMark website ([https://passmark.com](https://passmark.com)), to extract
benchmark data.
- 3 algorithms to retrieve hardware prices from the HardPrice website ([https://hardprice.ru](https://hardprice.ru),
RU/CIS region).
    - **Generation 1** (or a "fallback"). A stateless request to the static endpoint, json resource parsing.
    - **Generation 2**. Features:
        - Parsing contents of hardware item pages directly.
        - Maintaining a stateful scraping session: Cookies, CSRF token.
        - Processing async HTTP responses using MapReduce pattern.
    - **Generation 3** (so-called "T-1000", the most stable). Features:
        - Uses a real Google Chrome browser to read price data from the website (based on
            [itnelo/reactphp-webdriver](https://github.com/itnelo/reactphp-webdriver)).
        - Emulates human behavior with random actions, to bypass anti-bot measures and tarpits.
        - Maintaining a persistent and clean user identity.
- `TimerIssuerInterface` as a bridge between event loop internals and actual scraping logic. It controls time frames
and delays between scraping iterations.
- New containers in stack: `selenium-hub` and `selenium-node-chrome`; used by the 3rd gen price retrieving
algorithm, to get data from websites using a remote browser instance (see
[Selenium Grid](https://selenium.dev/documentation/en/grid)).
- `Benchmark\ListCommand` and `Price\ListCommand` to make a preview for data that will be used in the hardware
comparison and deal suggestion.
- README: installation guide (docker compose), console API description with asciicasts (for 2 commands).

### Changed

- Upgrading ReactPHP environment from 0.x to [1.x](https://github.com/reactphp/http/releases/tag/v1.0.0) LTS,
Symfony components — from 5.1 to 5.2+. Adopting Xdebug [3.x](https://xdebug.org/announcements/2020-11-25).

### Fixed

- SPA frontend is now properly handles 404 Not Found page for non-existent routes (Svelte).

### Security

- Rate limiting for incoming requests to the microservice' public endpoint (HAProxy).

This release introduces 2 console commands, `benchmark:list` — to download and render a list of hardware benchmark
results for the CPU category, `price:list` — prints a table with hardware prices (regions: RU/CIS; CPU category).

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

[Unreleased]: https://github.com/sterlett/sterlett/compare/0.2.0...0.x
[0.2.0]: https://github.com/sterlett/sterlett/compare/0.1.0..0.2.0
[0.1.0]: https://github.com/sterlett/sterlett/releases/tag/0.1.0