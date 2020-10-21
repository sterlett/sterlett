<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\HardPrice;

use ArrayIterator;
use IteratorIterator;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Sterlett\HardPrice\Parser\PriceParser;
use Sterlett\Hardware\PriceInterface;
use Throwable;
use Traversable;

/**
 * Collects price responses and builds an iterator to access price data, keyed by the specific hardware identifiers.
 *
 * It makes an iterator that returns a hardware identifier and related price DTOs on each iterating step (will be
 * parsed from the raw HTTP responses on the fly).
 *
 * Note: iterating behavior for the iterator instance, that will be created, is non-deterministic, i.e. for each
 * hardware identifier (as a key) there may be multiple price DTO lists (values), so it is not safe, for example, to
 * call iterator_to_array with default positive value for the "use_keys" flag.
 */
class PriceCollector
{
    /**
     * Transforms price data from the raw format to the list of application-level DTOs
     *
     * @var PriceParser
     */
    private PriceParser $priceParser;

    /**
     * PriceCollector constructor.
     *
     * @param PriceParser $priceParser Transforms price data from the raw format to the list of application-level DTOs
     */
    public function __construct(PriceParser $priceParser)
    {
        $this->priceParser = $priceParser;
    }

    /**
     * Returns a traversable list with pairs {hardware identifier => price DTOs}. Input contains raw data with hardware
     * prices from website.
     *
     * An iterable list, representing an element of returning collection, will be Traversable<PriceInterface> or
     * PriceInterface[].
     *
     * @param iterable $responseListById A map {id => responses} with raw price data from the website
     *
     * @return Traversable<iterable>
     */
    public function makeIterator(iterable $responseListById): Traversable
    {
        // todo: apply sorting behavior (from the most expensive to the cheapest ones)

        if ($responseListById instanceof Traversable) {
            $responseListIterator = new IteratorIterator($responseListById);
        } else {
            $responseListIterator = new ArrayIterator($responseListById);
        }

        $responseListIterator->rewind();

        for (; $responseListIterator->valid();) {
            $hardwareIdentifier = $responseListIterator->key();
            $responses          = $responseListIterator->current();

            try {
                $hardwarePrices = $this->parseResponses($hardwareIdentifier, $responses);
            } catch (Throwable $exception) {
                throw new RuntimeException('An error has been occurred during price response parsing.', 0, $exception);
            }

            yield from $hardwarePrices;

            $responseListIterator->next();
        }
    }

    /**
     * Returns an iterable list of hardware prices, extracted from the given responses.
     *
     * Response collection is expected as Traversable<ResponseInterface> or ResponseInterface[].
     *
     * @param int      $hardwareIdentifier Hardware identifier
     * @param iterable $responses          A list with responses which contains price data
     *
     * @return Traversable<PriceInterface>|PriceInterface[]
     */
    private function parseResponses(int $hardwareIdentifier, iterable $responses): iterable
    {
        /** @var ResponseInterface $response */
        foreach ($responses as $response) {
            $bodyContents = (string) $response->getBody();

            $hardwarePricesPartial = $this->priceParser->parse($bodyContents);

            yield $hardwareIdentifier => $hardwarePricesPartial;
        }
    }
}