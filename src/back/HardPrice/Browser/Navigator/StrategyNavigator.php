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

namespace Sterlett\HardPrice\Browser\Navigator;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\HardPrice\Browser\NavigatorInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

/**
 * Provides a navigation logic for the configured strategy (dynamically)
 */
class StrategyNavigator implements NavigatorInterface
{
    /**
     * Finds implementation for the configured navigation strategy
     *
     * @var ServiceLocator
     */
    private ServiceLocator $navigatorLocator;

    /**
     * A strategy name for the website access
     *
     * @var string
     */
    private string $navigationStrategy;

    /**
     * StrategyNavigator constructor.
     *
     * @param ServiceLocator $navigatorLocator   Finds implementation for the configured navigation strategy
     * @param string         $navigationStrategy A strategy name for the website access
     */
    public function __construct(ServiceLocator $navigatorLocator, string $navigationStrategy)
    {
        $this->navigatorLocator   = $navigatorLocator;
        $this->navigationStrategy = $navigationStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function navigate(BrowserContext $browserContext): PromiseInterface
    {
        try {
            /** @var NavigatorInterface $navigator */
            $navigator = $this->navigatorLocator->get($this->navigationStrategy);
        } catch (Throwable $exception) {
            $undefinedStrategyExceptionMessage = sprintf(
                "Unable to navigate. Undefined strategy '%s'.",
                $this->navigationStrategy
            );

            throw new RuntimeException($undefinedStrategyExceptionMessage, 0, $exception);
        }

        $navigationPromise = $navigator->navigate($browserContext);

        return $navigationPromise;
    }
}
