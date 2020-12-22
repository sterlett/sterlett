
# Sterlett

[![Build Status](https://travis-ci.com/sterlett/sterlett.svg?branch=0.x)](https://travis-ci.com/sterlett/sterlett)
[![CodeFactor](https://www.codefactor.io/repository/github/sterlett/sterlett/badge/0.x)](https://www.codefactor.io/repository/github/sterlett/sterlett/overview/0.x)

- [Goals](#goals)
- [Architecture](#architecture)
- [Starting microservice](#starting-microservice)
- [Console API](#console-api)
    - [Downloading a benchmark list](#downloading-a-benchmark-list)
    - [Retrieving hardware prices](#retrieving-hardware-prices)
- [Honeycomb](#honeycomb)
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

## Starting microservice

Clone the repository, then build a `.env` and other configuration files:

```
$ git clone git@github.com:sterlett/sterlett.git sterlett && cd "$_"
$ bin/configure-env dev
```

Run a compose project (or a stack):

```
$ docker-compose up -d
```

## Console API

### Downloading a benchmark list

Renders a list with benchmark results from the configured providers, which are used in the algorithm as a source
for hardware efficiency measure.

```
$ docker-compose run --rm --no-deps app bin/console benchmark:list
```

Example:

![console_api_benchmark_list_asciicast](.github/images/console-api-benchmark-list.gif)

### Retrieving hardware prices

Renders a table with hardware prices from the different sellers, which are used to suggest deals *.

> \* — Actually, a [FallbackProvider](src/back/Hardware/Price/Provider/HardPrice/FallbackProvider.php) will be used for
> price retrieving in the console environment; a normal run could take from 20 minutes to 2.5+ hours, due to some
> sophisticated scraping techs that are executing asynchronously, in the background, and guarantee a certain level of
> stability, while the microservice serves HTTP requests. See [BrowsingProvider](src/back/Hardware/Price/Provider/HardPrice/BrowsingProvider.php).

Currently supported regions: RU/CIS.

```
$ docker-compose run --rm --no-deps app bin/console price:list
```

Example:

![console_api_price_list_asciicast](.github/images/console-api-price-list.gif)

## Honeycomb

This one is currently at the prototyping stage :honeybee:.

:honey_pot: Backend base \
:honey_pot: Frontend base \
:honey_pot: Routing and load balancing capabilities \
:honey_pot: CI ground \
:honey_pot: Prices retrieving \
:honey_pot: Benchmarks retrieving \
:black_square_button: Data persistence \
:black_square_button: Console API: CPU list \
:black_square_button: Console API: Day/Week deals \
:black_square_button: Microservice: CPU list browsing

## See also

- [itnelo/reactphp-foundation](https://github.com/itnelo/reactphp-foundation) — A fresh skeleton
for building asynchronous microservices using PHP 7.4+, ReactPHP and Symfony 5 components,
with a deployment preset for scaling and load balancing.
- [itnelo/reactphp-webdriver](https://github.com/itnelo/reactphp-webdriver) — **Sterlett** uses the ReactPHP WebDriver, a
fast and non-blocking PHP client for the [Selenium](https://www.selenium.dev) browser automation engine,
to acquire data from some websites.

## Changelog

All notable changes to this project will be documented in [CHANGELOG.md](CHANGELOG.md).