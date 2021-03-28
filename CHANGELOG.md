
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- No description yet.

## [0.5.0] - 2021-03-28

This release introduces a deals API, which provides a list of hardware sell offers, sorted by the Price/Performance
indicator (regional prices, see [Price\ProviderInterface](src/back/Hardware/Price/ProviderInterface.php)).

### Added

- Database migrations to preserve PassMark values in the local storage, as well as price-benchmark bindings and ratio
datasets.
- `VBRatio\Saver` service and a related event listener, to persist Price/Performance ratio calculations for further
analysis.
- `VBRatio\BindingsUpdater` to save price-benchmark relations (bindings).
- A benchmark retrieving routine is registered (with an existing provider) to make a cache with hardware performance
data.
- A set of components, responsible for deal suggestion:
    - [Deal analyser](src/back/Hardware/Deal/Analyser.php) service to find better prices for the available performance
    offers (utilizing [MySQL 8 sliding windows](https://dev.mysql.com/doc/refman/8.0/en/window-functions.html)).
    - Deal observer, to forward analyser data (sell offers) to the different application domains (incl. HTTP handlers)
    using an event dispatcher.
    - `DealsSuggestedEvent` to hold sell offers, `app.deal.rankings` and `app.deal.per_rank` parameters to configure
    analyser outputs.
- Extracting and rendering a preview image for hardware items (price retrieving routine).
- Front: a `Divider` component to make menu categories (Svelte).
- Front: fetching CPU deals from the API handler, `Deal\Viewer` and `Hardware\Rank` components to visualize sell offers
and performance categories.
- Front: `sortEnable` flag and a set of properties for column formatting (table component).
- Front — pages: description for the hardware categories and minor FAQ adjustments (ru-RU locale).
- Front — miscellaneous: a tooltip for PassMark column in the hardware representation table to describe benchmark
conditions (multi-core / single thread / etc.).

### Changed

- SPA frontend is now updates page metadata using the Context API and a Store contract instead of Component/DOM events
(client-side routing w/ Svelte).

### Fixed

- Ignoring some price records from the stores without a convenient currency for the RU/CIS region, to maintain more
reliable (regional) Price/Performance rating.
- Front: correct float parsing for the price column in the hardware representation table (Svelte).
- Front: normalizing a table component for the small viewpoints (sm/600px breakpoint).
- Front: setting up node resolve plugin properly, to remove `.js` and `.svelte` extensions from the ES6 import
directives.
- Front: preventing page "jumps" while switching between pages with different content sizes - normalizing an `overflow`
property (always showing a scrollbar).

### Security

- Upgrading Svelte environment from `3.24` to `3.35` +fixes for BC breaks: in the event system
([get_current_component](https://github.com/sveltejs/svelte/blob/v3.35.0/src/runtime/internal/lifecycle.ts#L9) is
starting to raise errors outside the component system context, as intended, — but previously it was possible to bypass
those checks accidentally, under some circumstances) and
[bundler configuration](https://github.com/sveltejs/rollup-plugin-svelte/blob/master/CHANGELOG.md#700).

## [0.4.0] - 2021-03-05

This release makes it possible to use a browsing provider (based on the
[async PHP WebDriver](https://github.com/itnelo/reactphp-webdriver)) for the price retrieving routine (stability
improvements) and adds a `--source=database` option for the `ratio:calculate` command to perform semi-offline
calculations (benchmark data from the live providers is still needed by this point).

The tool gets its demo website [www.cpu-junkie.ru](http://cpu-junkie.ru) (the first version @0.4.0).

### Added

- Symfony Bridge: `DeferredEventInterface`, patch for the default event dispatcher to enable async mode (candidate for
extracting).
- `VBRatio\Feeder` service and `VBRatiosEmittedEvent` to transfer calculated price/benchmark data to the async HTTP
handlers.
- New background tasks: a `VBRatio\FeedingRoutine` to execute V/B ratios transfer logic using the centralized event
loop.
- Option `--source` has been added for the `ratio:calculate` command and acts as a switch between modes: `live`
(a fresh dataset from the third-party web resources) and `database` (a local cache of the existing process).
- `RepositoryProvider` (price records) to support `--source=database` option of the calculation command.
- `Price\Repository`: executing fetch queries asynchronously w/ result sets hydration.
- Configurable parameters to manage WebDriver behavior, depending on the available CPU cycles. Each of them may
decrease the overall load (CPU/Mem) for a single browser node:
	- `selenium.command.timeout` — how long to wait for the WebDriver's response, before termination (default: 30.0
	sec).
	- `selenium.ajax.timeout` — to regulate available time for ajax results rendering / waitUntil condition checks
	(default: 30.0 sec).
	- `selenium.state.check_frequency` — sets an interval for the waitUntil calls; for example, it affects element
	visibility checks (default: 0.5 sec).
	- `app.browser.enable_cleaner` — if set as "true", the WebDriver session will be closed on each successful data
	retrieving operation, so the system will not have to deal with the higher memory footprint. But such behavior
	causes extra requests to the third-party web sources and it could compromise the scraping routine (default: false).
- It is now possible to change the lower bound of random delays for scraping iterations
(parameter `hardprice.requests_delay_min`).
- Configurable V-logging level for the Chrome executable (environment variable `SELENIUM_NODE_CHROME_LOG_LEVEL`;
will enable/disable `chrome_debug.log`).
- Front: accepting data from the HTTP handler, table sorting & column formatters (Svelte).
- Front — pages: HTTP API, Console API, About (ru-RU locale).
- Front — miscellaneous: +GitHub button, layout adjustments.

### Changed

- Refactoring for the service, which will open a remote browser to perform scraping actions. Extracting
`Browser\OpenerInterface` with `ExistingSessionOpener` and `NewSessionOpener` implementations. Now, the existing
WebDriver session will be picked up by default (if available).
- Refactoring for the website navigation service (extracting `Browser\NavigatorInterface`). `ReferrerNavigator`
implementation will open a website by clicking a configured link on the website-referrer (currently available
referrers: Google search engine, VKontakte social media).

### Fixed

- Retry logic for the browser components to bypass the "connection closed unexpectedly" error in some situations (gen 3 price retrieving).
- Ignoring some hardware items with "out-of-stock" marks (price parser).

## [0.3.0] - 2021-02-01

This release introduces a `ratio:calculate` console command — to render a table with numerical scores for each
available hardware item and measure its customer appeal in terms of price/performance (using regional prices).
The microservice is starting to persist hardware prices in the local storage on-the-fly (a background routine).

### Added

- A configuration option for the PassMark provider, to define a minimal benchmark value that must be scored by the
hardware item (`passmark.cpu.min_value`; see `Benchmark\ValueThresholdIterator`).
- `RoutineInterface` as an interface for background tasks and a `Price\RetrievingRoutine`, which collects hardware
prices, while the microservice serves HTTP requests (gen 1 "fallback" algorithm is used; in testing).
- New containers in stack: `database` (MySQL 8 docker image); used by the price retrieving routine to persist hardware
data and price tags.
- `migrations:status`, `migrations:migrate` and `migrations:diff` command to manage database migrations, with a custom
schema provider for diffing without ORM layer (using [doctrine/migrations](https://github.com/doctrine/migrations)).
- Migration for the `hardware_price` table.
- Async `Price\Repository` service to persist price records in the database (using
[react/mysql](https://github.com/friends-of-reactphp/mysql)).
- `VBRatioInterface` and `VBRatio` data object implementation to store and transfer Value/Benchmark calculation results
([learn more](README.md#calculating-vb-rating)).
- A set of components, responsible for the V/B rating calculation:
    - `ProviderInterface` and its `ConfigurableProvider` implementation, mixins for both blocking (Console API) and
    async (Microservice) scopes.
    - [SourceBinder](src/back/Hardware/VBRatio/SourceBinder.php) service, which connects benchmarks with related price
    lists from the third-party web resources.
	- V/B ratio calculator.
- `VBRatio\CalculateCommand` implementation for the command-line interface.

### Fixed

- Various fixes and adjustments for `.travis.yml`, `README.md` and other metafiles.

## [0.2.0] (mvp) - 2020-12-25

This release introduces 2 console commands, `benchmark:list` — to download and render a list of hardware benchmark
results for the CPU category, `price:list` — prints a table with hardware prices (regions: RU/CIS; CPU category).

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
- `TimeIssuerInterface` as a bridge between event loop internals and actual scraping logic. It controls time frames
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

[Unreleased]: https://github.com/sterlett/sterlett/compare/0.5.0...0.x
[0.5.0]: https://github.com/sterlett/sterlett/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/sterlett/sterlett/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/sterlett/sterlett/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/sterlett/sterlett/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/sterlett/sterlett/releases/tag/0.1.0