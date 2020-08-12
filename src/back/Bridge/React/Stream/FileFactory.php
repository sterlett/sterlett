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

namespace Sterlett\Bridge\React\Stream;

use Exception;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use RuntimeException;

/**
 * Provides an interface for opening file as React's readable/writable resource stream.
 *
 * For more advanced file abstraction see "react/filesystem".
 */
final class FileFactory
{
    /**
     * Returns readable resource stream instance representing a file in the local filesystem
     *
     * @param string        $filename Path to file
     * @param string        $mode     Mode for fopen call
     * @param LoopInterface $loop     Event loop
     *
     * @return ReadableResourceStream
     *
     * @throws RuntimeException if fopen() call is failed
     */
    public static function getReadableFile(string $filename, string $mode, LoopInterface $loop): ReadableResourceStream
    {
        try {
            $resource = fopen($filename, $mode);
        } catch (Exception $exception) {
            $exceptionOuterMessage = sprintf(
                "Unable to create readable resource stream for file with filename '%s' and mode '%s'.",
                $filename,
                $mode
            );

            throw new RuntimeException($exceptionOuterMessage, 0, $exception);
        }

        return new ReadableResourceStream($resource, $loop);
    }
}
