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

namespace Sterlett\Browser;

use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;

/**
 * A shared storage that holds current state of browsing process (gen 3 algorithm)
 *
 * todo: (gen 3)
 */
class Context
{
    private TimeIssuerInterface $browsingThread;

    private string $hubSession;

    private array $tabIdentifiers;

    /**
     * @return TimeIssuerInterface
     */
    public function getBrowsingThread(): TimeIssuerInterface
    {
        return $this->browsingThread;
    }

    /**
     * @param TimeIssuerInterface $browsingThread
     */
    public function setBrowsingThread(TimeIssuerInterface $browsingThread): void
    {
        $this->browsingThread = $browsingThread;
    }

    /**
     * @return string
     */
    public function getHubSession(): string
    {
        return $this->hubSession;
    }

    /**
     * @param string $hubSession
     */
    public function setHubSession(string $hubSession): void
    {
        $this->hubSession = $hubSession;
    }

    /**
     * @return array
     */
    public function getTabIdentifiers(): array
    {
        return $this->tabIdentifiers;
    }

    /**
     * @param array $tabIdentifiers
     */
    public function setTabIdentifiers(array $tabIdentifiers): void
    {
        $this->tabIdentifiers = $tabIdentifiers;
    }
}
