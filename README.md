
# Sterlett

[![Build Status](https://travis-ci.com/sterlett/sterlett.svg?branch=master)](https://travis-ci.com/sterlett/sterlett)
[![CodeFactor](https://www.codefactor.io/repository/github/sterlett/sterlett/badge)](https://www.codefactor.io/repository/github/sterlett/sterlett)

- [Goals](#goals)
- [Architecture](#architecture)
- [Honeycomb](#honeycomb)
- [Console API](#console-api)
    - [List of benchmarks](#list-of-benchmarks)
- [See also](#see-also)
- [Changelog](#changelog)

## Goals

**Sterlett** is a microservice and console API for retrieving and processing public information
about computer hardware prices. It may help to buy the most efficient microchips in your region
by the lowest price, using several benchmark providers and taking into account local currency spikes
and pricing fraud.

## Architecture

The microservice represents a set of backend and frontend containers behind a gateway
for routing and load balancing.

Backend: PHP 7.4+, [ReactPHP](https://github.com/reactphp/reactphp), 
[Symfony](https://github.com/symfony/symfony) 5 components. \
Frontend: [Lighttpd](https://lighttpd.net) 1.4, JavaScript (ES5, ES6+),
[Svelte](https://github.com/sveltejs/svelte) 3, [Spectre.css](https://github.com/picturepan2/spectre). \
Gateway: [HAProxy](https://www.haproxy.com) 2.2.

## Honeycomb

This one is currently at the prototyping stage :honeybee:.

:honey_pot: Backend base \
:honey_pot: Frontend base \
:honey_pot: Routing and load balancing capabilities \
:honey_pot: CI ground \
:black_square_button: Prices retrieving \
:honey_pot: Benchmarks retrieving \
:black_square_button: Data persistence \
:black_square_button: Console API: CPU list \
:black_square_button: Console API: Day/Week deals \
:black_square_button: Microservice: CPU list browsing

## Console API

### List of benchmarks

Renders a list with benchmark results from the configured providers, which are used in the algorithm as a source
for hardware efficiency measure.

```
$ docker-compose run --rm app bin/console benchmark:list
```

Example:

![console_api_benchmark_list_asciicast](https://github.com/sterlett/sterlett/blob/0.x/.github/images/console-api-benchmark-list.gif)

## See also

- [itnelo/reactphp-foundation](https://github.com/itnelo/reactphp-foundation) — A fresh skeleton
for building asynchronous microservices using PHP 7.4+, ReactPHP and Symfony 5 components,
with a deployment preset for scaling and load balancing.
- [itnelo/reactphp-webdriver](https://github.com/itnelo/reactphp-webdriver) — **Sterlett** uses the ReactPHP WebDriver, a
fast and non-blocking PHP client for the [Selenium](https://www.selenium.dev) browser automation engine,
to acquire data from some websites.

## Changelog

All notable changes to this project will be documented in [CHANGELOG.md](CHANGELOG.md).