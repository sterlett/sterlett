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

namespace Sterlett\Repository;

use React\MySQL\QueryResult;
use Traversable;

/**
 * Transforms a raw dataset from the async database driver to a list of application-level DTOs
 */
interface HydratorInterface
{
    /**
     * Returns a collection of domain-specific objects, filled with the result sets data
     *
     * @param QueryResult $queryResult A set of raw data arrays from the async database driver
     *
     * @return Traversable<object>|object[]
     */
    public function hydrate(QueryResult $queryResult): iterable;

    /**
     * Returns an array of field names for the domain-specific object
     *
     * @return array
     */
    public function getFieldNames(): array;
}
