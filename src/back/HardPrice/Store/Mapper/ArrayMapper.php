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

namespace Sterlett\HardPrice\Store\Mapper;

use RuntimeException;
use Sterlett\HardPrice\Store\MapperInterface;

/**
 * Provides store (seller) slug using the given array with mappings
 *
 * todo: implement mapper, based on the api resource
 */
class ArrayMapper implements MapperInterface
{
    /**
     * Predefined {external id => slug} mappings
     *
     * @var array
     */
    private array $slugByIdMap;

    /**
     * ArrayMapper constructor.
     *
     * @param array $slugByIdMap Predefined {external id => slug} mappings
     */
    public function __construct(array $slugByIdMap)
    {
        $this->slugByIdMap = $slugByIdMap;
    }

    /**
     * {@inheritDoc}
     */
    public function requireSlug(int $externalIdentifier): string
    {
        if (!array_key_exists($externalIdentifier, $this->slugByIdMap)) {
            $slugNotFoundMessage = sprintf(
                "Slug for the store with identifier '%s' is not found.",
                $externalIdentifier
            );

            throw new RuntimeException($slugNotFoundMessage);
        }

        $storeSlug = $this->slugByIdMap[$externalIdentifier];

        return $storeSlug;
    }
}
