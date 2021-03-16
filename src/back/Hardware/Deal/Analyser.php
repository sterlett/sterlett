<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Hardware\Deal;

use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Executes analytic queries for the available hardware data and returns hydrated result sets for the consumer-side
 */
class Analyser
{
    /**
     * An async database connection to the price storage
     *
     * @var ConnectionInterface
     */
    private ConnectionInterface $databaseConnection;

    /**
     * Analyser constructor.
     *
     * @param ConnectionInterface $databaseConnection An async database connection to the price storage
     */
    public function __construct(ConnectionInterface $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * Returns a promise that resolves to a collection of data records for all configured benchmark ranges at once
     *
     * @param array $benchmarkRanges A set of [from, to] arrays that defines configuration for the sliding window
     *
     * @return PromiseInterface<iterable>
     */
    public function slidingWindowAll(array $benchmarkRanges): PromiseInterface
    {
        $slidingWindowPromises = [];

        foreach ($benchmarkRanges as $benchmarkRange) {
            $benchmarkFrom = $benchmarkRange['from'];
            $benchmarkTo   = $benchmarkRange['to'];

            $slidingWindowPromise = $this->getSlidingWindow($benchmarkFrom, $benchmarkTo);

            $slidingWindowPromises[] = $slidingWindowPromise;
        }

        return all($slidingWindowPromises);
    }

    /**
     * Returns a promise that resolves to an array with price data, to analyse hardware deals in the specified
     * performance range
     *
     * @param float $benchmarkFrom The lower boundary of the benchmark values
     * @param float $benchmarkTo   The upper benchmark values boundary
     *
     * @return PromiseInterface<array>
     */
    public function getSlidingWindow(float $benchmarkFrom, float $benchmarkTo): PromiseInterface
    {
        // todo: inject table names from the configuration parameters
        $statementSelect = <<<SQL
            SELECT DISTINCT
                b.`hardware_name`,
                p.`hardware_image_uri`,
                p.`seller_name`,
                FIRST_VALUE(p.`price_amount`) OVER `sw_seller_latest_prices` AS 'price_amount',
                p.`currency_label`,
                FIRST_VALUE(b.`value`) OVER `sw_benchmark_latest_values` as 'benchmark_value',
                FIRST_VALUE(ROUND(b.`value` / p.`price_amount` * 100, 2)) OVER `sw_seller_latest_prices` AS 'vb_ratio',
                FIRST_VALUE(p.`created_at`) OVER `sw_seller_latest_prices` AS 'created_at'
            FROM
                `hardware_price_cpu` p
            INNER JOIN `hardware_benchmark_hardware_price` b2p ON p.`hardware_name` = b2p.`price_hardware_name`
            INNER JOIN `hardware_benchmark_passmark` b ON b.`hardware_name` = b2p.`benchmark_hardware_name`
            INNER JOIN `hardware_ratio` r ON r.`hardware_name` = b2p.`benchmark_hardware_name`
            WHERE
                -- the most recent price records
                p.`created_at` > (SELECT DATE(MAX(created_at)) FROM `hardware_price_cpu`)
                -- PassMark range of interest
                AND b.`value` > ?
                AND b.`value` < ?
            -- capturing related frames to perform extended aggregation
            WINDOW `sw_seller_latest_prices` AS (
                PARTITION BY
                    b.`hardware_name`,
                    p.`seller_name`
                ORDER BY
                    p.`created_at` DESC,
                    p.`id` DESC
                -- always capturing from the start, no shifts
                ROWS UNBOUNDED PRECEDING
            ),
            `sw_benchmark_latest_values` AS (
                PARTITION BY
                    b.`hardware_name`
                ORDER BY
                    b.`created_at` DESC,
                    b.`id` DESC
                ROWS UNBOUNDED PRECEDING
            )
            ORDER BY
                vb_ratio DESC,
                price_amount ASC,
                created_at DESC
            ;
SQL;

        $queryResultPromise = $this->databaseConnection
            // requesting data from the local storage.
            ->query($statementSelect, [$benchmarkFrom, $benchmarkTo])
            // hydrating to the DTO set.
            ->then(fn (QueryResult $queryResult) => $this->hydrate($queryResult))
        ;

        return $queryResultPromise;
    }

    /**
     * Returns a collection of records with hardware data from the sliding window (deals by sellers, sorted by the
     * price and performance indicators)
     *
     * @param QueryResult $queryResult Result sets from the database driver
     *
     * @return iterable
     */
    public function hydrate(QueryResult $queryResult): iterable
    {
        foreach ($queryResult->resultRows as $resultRow) {
            // todo: dto bindings / normalizations
            $dealBySeller = [
                'name'  => $resultRow['hardware_name'],
                'image' => $resultRow['hardware_image_uri'],

                'prices' => [
                    [
                        'type'      => $resultRow['seller_name'],
                        'value'     => (int) $resultRow['price_amount'],
                        'currency'  => $resultRow['currency_label'],
                        'precision' => 0,
                    ],
                ],

                'vb_ratio' => $resultRow['vb_ratio'],

                'benchmarks' => [
                    [
                        'name'  => 'PassMark',
                        'value' => (int) $resultRow['benchmark_value'],
                    ],
                ],

                'actual_to' => $resultRow['created_at'],
            ];

            yield $dealBySeller;
        }
    }
}
