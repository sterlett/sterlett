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

namespace Sterlett\HardPrice\Browser;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Dto\Hardware\Item;
use Sterlett\HardPrice\Browser\ItemSearcher\SearchBarLocator;
use function React\Promise\reject;

/**
 * Performs actions in the remove browser to find a page with hardware item information
 */
class ItemSearcher
{
    /**
     * @var SearchBarLocator
     */
    private SearchBarLocator $searchBarLocator;

    public function __construct(SearchBarLocator $searchBarLocator)
    {
        $this->searchBarLocator = $searchBarLocator;
    }

    public function searchItem(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        // todo (gen 3)

        return reject(new RuntimeException('todo'));
    }
}
