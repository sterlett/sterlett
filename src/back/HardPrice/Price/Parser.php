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

namespace Sterlett\HardPrice\Price;

use RuntimeException;
use Sterlett\Dto\Hardware\Price;
use Sterlett\HardPrice\Price\Parser\Tokenizer as PropertyTokenizer;
use Sterlett\HardPrice\Store\MapperInterface as StoreMapperInterface;
use Traversable;

/**
 * Transforms price data from the raw format to the list of application-level DTOs
 */
class Parser
{
    /**
     * Recognizes data primitives to make properties for the price DTOs from the response message body
     *
     * @var PropertyTokenizer
     */
    private PropertyTokenizer $propertyTokenizer;

    /**
     * Maps an external store identifier to the related store (seller)
     *
     * @var StoreMapperInterface
     */
    private StoreMapperInterface $storeMapper;

    /**
     * Parser constructor.
     *
     * @param PropertyTokenizer    $propertyTokenizer Recognizes data primitives to make properties for the price DTOs
     * @param StoreMapperInterface $storeMapper       Maps an external store identifier to the related store (seller)
     */
    public function __construct(PropertyTokenizer $propertyTokenizer, StoreMapperInterface $storeMapper)
    {
        $this->propertyTokenizer = $propertyTokenizer;
        $this->storeMapper       = $storeMapper;
    }

    /**
     * Returns a list with hardware price DTOs
     *
     * @param string $data Price data in raw format
     *
     * @return Traversable<Price>|Price[]
     */
    public function parse(string $data): iterable
    {
        $propertyDataArray = $this->propertyTokenizer->tokenize($data);

        foreach ($propertyDataArray as $propertyData) {
            /** @var int $sellerExternalId */
            [$storeExternalId, $priceAmount] = $propertyData;

            try {
                $sellerIdentifier = $this->storeMapper->requireSlug($storeExternalId);
            } catch (RuntimeException $exception) {
                $undefinedStoreExceptionMessage = sprintf(
                    'Unable to parse a price record: undefined store (%s).',
                    $storeExternalId
                );

                throw new RuntimeException($undefinedStoreExceptionMessage);
            }

            $hardwarePrice = new Price();
            $hardwarePrice->setSellerIdentifier($sellerIdentifier);
            $hardwarePrice->setAmount($priceAmount);
            $hardwarePrice->setPrecision(0);
            $hardwarePrice->setCurrency('RUR');

            yield $hardwarePrice;
        }
    }
}
