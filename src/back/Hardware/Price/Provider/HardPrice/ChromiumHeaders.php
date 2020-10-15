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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

/**
 * A helper that is used to convert headers set to the form of typical XMLHttpRequest (or navigation request) from the
 * Chromium-based browsers
 */
final class ChromiumHeaders
{
    /**
     * Returns an upgraded set of headers for the request, with additional lines from the typical XMLHttpRequest
     *
     * @param array $headers Array of headers for the request
     *
     * @return array
     */
    public static function makeFrom(array $headers): array
    {
        if (array_key_exists('Cookie', $headers)) {
            $headerSetUpgraded = self::addXhrHeaders($headers);
        } else {
            $headerSetUpgraded = self::addNavigationHeaders($headers);
        }

        return $headerSetUpgraded;
    }

    /**
     * Returns a set of request headers with common payload to make it similar to the typical XMLHttpRequest
     *
     * @param array $headers Array of headers for the request
     *
     * @return array
     */
    private static function addXhrHeaders(array $headers): array
    {
        $headerSetUpgraded = [];

        // preserving headers order.
        $headerSetUpgraded['Accept']          = '*/*';
        $headerSetUpgraded['Accept-Language'] = 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';

        if (array_key_exists('Content-Length', $headers)) {
            $headerSetUpgraded['Content-Length'] = '';
        }

        if (array_key_exists('Content-Type', $headers)) {
            $headerSetUpgraded['Content-Type'] = '';
        }

        $headerSetUpgraded['Cookie'] = '';
        $headerSetUpgraded['DNT']    = '1';

        $headerSetUpgraded['Origin']  = 'https://hardprice.ru';
        $headerSetUpgraded['Referer'] = 'https://hardprice.ru/';

        $headerSetUpgraded['Sec-Fetch-Dest'] = 'empty';
        $headerSetUpgraded['Sec-Fetch-Mode'] = 'cors';
        $headerSetUpgraded['Sec-Fetch-Site'] = 'same-origin';

        $headerSetUpgraded['User-Agent'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
            . 'Chrome/85.0.4183.121 Safari/537.36';

        if (array_key_exists('X-CSRF-TOKEN', $headers)) {
            $headerSetUpgraded['X-CSRF-TOKEN'] = '';
        }

        $headerSetUpgraded['X-Requested-With'] = 'XMLHttpRequest';

        $headerSetUpgraded = array_replace($headerSetUpgraded, $headers);

        $headerSetUpgraded['Cookie'] .= '; region_v2=1';

        return $headerSetUpgraded;
    }

    /**
     * Returns a set of request headers with common payload for navigation between pages
     *
     * @param array $headers Array of headers for the request
     *
     * @return array
     */
    private static function addNavigationHeaders(array $headers): array
    {
        $headerSetUpgraded = [];

        $headerSetUpgraded['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,' .
            'image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';

        $headerSetUpgraded['Accept-Language'] = 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
        $headerSetUpgraded['Cache-Control']   = 'max-age=0';

        $headerSetUpgraded['Cookie'] = 'region_v2=1';
        $headerSetUpgraded['DNT']    = '1';

        $headerSetUpgraded['Sec-Fetch-Dest'] = 'document';
        $headerSetUpgraded['Sec-Fetch-Mode'] = 'navigate';
        $headerSetUpgraded['Sec-Fetch-Site'] = 'none';

        $headerSetUpgraded['Sec-Fetch-User']            = '?1';
        $headerSetUpgraded['Upgrade-Insecure-Requests'] = '1';

        $headerSetUpgraded['User-Agent'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) ' .
            'Chrome/85.0.4183.121 Safari/537.36';

        $headerSetUpgraded = array_replace($headerSetUpgraded, $headers);

        return $headerSetUpgraded;
    }
}
