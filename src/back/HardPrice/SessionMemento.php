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

/**
 * Holds a shared context with authentication data (cookies, tokens, etc.) to maintain a single browsing session during
 * price parsing
 */
final class SessionMemento
{
    /**
     * Path to the local cache directory to manage scraping session persistence
     *
     * @var string
     */
    private string $cacheDirPath;

    /**
     * Represents a shared browsing session
     *
     * @var Authentication|null
     */
    private ?Authentication $_session;

    /**
     * SessionMemento constructor.
     *
     * @param string $projectDirPath Path to the local cache directory to manage scraping session persistence
     */
    public function __construct(string $projectDirPath)
    {
        // todo: refactoring required; extract to a separate event listener

        $this->cacheDirPath = $projectDirPath;

        $cacheFile = $this->cacheDirPath . '/hardprice.scraping.session-token';

        if (file_exists($cacheFile)) {
            $cookieAggregated = file_get_contents($cacheFile);
        } else {
            $cookieAggregated = false;
        }

        if (false !== $cookieAggregated) {
            $this->_session = new Authentication();

            $sessionCookies = explode(';', $cookieAggregated);

            foreach ($sessionCookies as $sessionCookie) {
                $this->_session->addCookie($sessionCookie);
            }
        } else {
            $this->_session = null;
        }
    }

    /**
     * Returns a session data or a NULL, if there is no browsing session in active state
     *
     * @return Authentication|null
     */
    public function getSession(): ?Authentication
    {
        return $this->_session;
    }

    /**
     * Sets data for the browsing session
     *
     * @param Authentication $session Session data
     *
     * @return void
     */
    public function setSession(Authentication $session): void
    {
        // todo: extract

        $sessionCookies   = $session->getCookies();
        $cookieAggregated = implode(';', $sessionCookies);

        $sessionCookiesCurrent   = $this->_session instanceof Authentication ? $this->_session->getCookies() : [];
        $cookieAggregatedCurrent = implode(';', $sessionCookiesCurrent);

        if ($cookieAggregated !== $cookieAggregatedCurrent) {
            file_put_contents($this->cacheDirPath . '/hardprice.scraping.session-token', $cookieAggregated);
        }

        $this->_session = $session;
    }
}
