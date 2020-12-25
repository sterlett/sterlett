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

namespace Sterlett\Bridge\React\Http\Response\Middleware\BuffererMiddleware;

use Traversable;

/**
 * Holds response body chunks
 */
class ChunkBag
{
    /**
     * A list of body chunks in the bag
     *
     * @var Traversable<string>|string[]
     */
    private iterable $chunks;

    /**
     * ChunkBag constructor.
     */
    public function __construct()
    {
        $this->chunks = [];
    }

    /**
     * Returns a list of body chunks
     *
     * @return Traversable<string>|string[]
     */
    public function getChunks(): iterable
    {
        return $this->chunks;
    }

    /**
     * Adds a single body chunk to the bag
     *
     * @param string $chunk A single body chunk
     *
     * @return void
     */
    public function addChunk(string $chunk): void
    {
        $this->chunks[] = $chunk;
    }
}
