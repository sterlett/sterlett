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
use React\Stream\WritableResourceStream;
use RuntimeException;

/**
 * Provides an interface for opening file as React's readable/writable resource stream and isolates creation of
 * internal descriptor from any IoC implementations which uses serialization for some dirty magic.
 *
 * For more advanced file abstraction see "react/filesystem".
 *
 * @see https://github.com/symfony/dependency-injection/blob/v5.1.0/Compiler/ResolveInstanceofConditionalsPass.php#L101
 *
 * todo: buffer option support
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
     */
    public static function getReadableFile(string $filename, string $mode, LoopInterface $loop): ReadableResourceStream
    {
        $resource = self::createResource($filename, $mode);

        return new ReadableResourceStream($resource, $loop);
    }

    /**
     * Returns writable resource stream instance representing a file in the local filesystem
     *
     * @param string        $filename Path to file
     * @param string        $mode     Mode for fopen call
     * @param LoopInterface $loop     Event loop
     *
     * @return WritableResourceStream
     */
    public static function getWritableFile(string $filename, string $mode, LoopInterface $loop): WritableResourceStream
    {
        $resource = self::createResource($filename, $mode);

        return new WritableResourceStream($resource, $loop);
    }

    /**
     * Creates and returns a file descriptor using specified filename and mode
     *
     * @param string $filename Path to file
     * @param string $mode     Mode for fopen call
     *
     * @return resource
     *
     * @throws RuntimeException if fopen() call is failed
     */
    private static function createResource(string $filename, string $mode)
    {
        try {
            $resource = fopen($filename, $mode);
        } catch (Exception $exception) {
            $exceptionOuterMessage = sprintf(
                "Unable to create resource stream for file with filename '%s' and mode '%s'.",
                $filename,
                $mode
            );

            throw new RuntimeException($exceptionOuterMessage, 0, $exception);
        }

        return $resource;
    }
}
