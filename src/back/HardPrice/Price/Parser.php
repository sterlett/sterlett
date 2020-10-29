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

use Sterlett\HardPrice\Price\Parser\Tokenizer as PropertyTokenizer;
use Sterlett\Hardware\PriceInterface;
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
     * Parser constructor.
     *
     * @param PropertyTokenizer $propertyTokenizer Recognizes data primitives to make properties for the price DTOs
     */
    public function __construct(PropertyTokenizer $propertyTokenizer)
    {
        $this->propertyTokenizer = $propertyTokenizer;
    }

    /**
     * Returns a list with hardware price DTOs
     *
     * @param string $data Price data in raw format
     *
     * @return Traversable<PriceInterface>|PriceInterface[]
     */
    public function parse(string $data): iterable
    {
        // todo: make DTOs

        return $this->propertyTokenizer->tokenize($data);
    }
}
