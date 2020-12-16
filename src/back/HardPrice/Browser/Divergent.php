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

use Itnelo\React\WebDriver\WebDriverInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;

/**
 * Performs random actions in the remote browser to confuse some website protection systems, which detects browser
 * automation
 */
class Divergent
{
    /**
     * Returns a promise that will be resolved when service finishes a set of random actions on the website
     *
     * @param WebDriverInterface $webDriver         Driver reference to perform actions in the remote browser instance
     * @param string             $sessionIdentifier A token to communicate with Selenium Grid (hub) server
     *
     * @return PromiseInterface<null>
     */
    public function randomAction(WebDriverInterface $webDriver, string $sessionIdentifier): PromiseInterface
    {
        $mouseMovePromise = $this
            ->randomMouseMove($webDriver, $sessionIdentifier)
            ->then(
                null,
                function (Throwable $rejectionReason) {
                    throw new RuntimeException('Unable to perform a random action.', 0, $rejectionReason);
                }
            )
        ;

        return $mouseMovePromise;
    }

    /**
     * Returns a promise that will be resolved when service completes moving a mouse pointer in the random direction on
     * the website (using a remote browser via web driver)
     *
     * @param WebDriverInterface $webDriver         Driver reference to perform actions in the remote browser instance
     * @param string             $sessionIdentifier A token to communicate with Selenium Grid (hub) server
     *
     * @return PromiseInterface<null>
     */
    private function randomMouseMove(WebDriverInterface $webDriver, string $sessionIdentifier): PromiseInterface
    {
        $aimingPromise = $webDriver->getElementIdentifier(
            $sessionIdentifier,
            '//div[contains(@class, "navbar-header")]//a[@href="/"]'
        );

        $pointerMovePromise = $aimingPromise->then(
            function (array $startingPointIdentifier) use ($webDriver, $sessionIdentifier) {
                $divergenceOffsetX = random_int(0, 20);
                $divergenceOffsetY = random_int(0, 5);

                return $webDriver->mouseMove(
                    $sessionIdentifier,
                    $divergenceOffsetX,
                    $divergenceOffsetY,
                    100,
                    $startingPointIdentifier
                );
            }
        );

        return $pointerMovePromise;
    }
}
