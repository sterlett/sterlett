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

namespace Sterlett\Bridge\WyriHaximus\React\Psr3\Stdio;

use DateTime;
use DateTimeInterface;
use Psr\Log\AbstractLogger;
use WyriHaximus\React\PSR3\Stdio\StdioLogger;

/**
 * Prepends each log message with formatted date and time
 *
 * TODO: this logger is a temporary/prototype solution due to poor design of Stdio library, should be replaced later
 */
class DateTimeAwareLogger extends AbstractLogger
{
    /**
     * Placeholder name to represent a date and time stamp
     *
     * @var string
     */
    private const PLACEHOLDER_DATE_TIME = '__dateTime';

    /**
     * Base logger implementation
     *
     * @var StdioLogger
     */
    private StdioLogger $logger;

    /**
     * Date and time format for message
     *
     * @var string
     */
    private string $format;

    /**
     * DateTimeAwareLogger constructor.
     *
     * @param StdioLogger $logger Base logger implementation
     * @param string      $format Date and time format for message
     */
    public function __construct(StdioLogger $logger, string $format = DateTimeInterface::ATOM)
    {
        $this->logger = $logger;
        $this->format = $format;
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = [])
    {
        $message = (string) $message;
        $message = '[{' . self::PLACEHOLDER_DATE_TIME . '}] ' . $message;

        if (!array_key_exists(self::PLACEHOLDER_DATE_TIME, $context)) {
            $dateTimeNow = new DateTime();

            $context[self::PLACEHOLDER_DATE_TIME] = $dateTimeNow->format($this->format);
        }

        $this->logger->log($level, $message, $context);
    }
}
