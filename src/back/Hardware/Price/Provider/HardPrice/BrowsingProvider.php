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

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Generator;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Sterlett\Dto\Hardware\Item;
use Sterlett\HardPrice\Item\Parser as ItemParser;
use Sterlett\HardPrice\Price\Parser as PriceParser;
use Sterlett\HardPrice\Store\Mapper\ArrayMapper;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;

/**
 * Gen 3 algorithm for price data retrieving from the HardPrice website.
 *
 * Emulates user behavior while traversing site pages using browser API (non-headless mode; headless mode is detected
 * in this case).
 *
 * todo: gen 3 algo refactoring (solid/grasp decomposition, code style)
 * todo: move from blocking php-webdriver/webdriver to native async driver (at ReactPHP bridge layer)
 */
class BrowsingProvider implements ProviderInterface
{
    /**
     * @var TimeIssuerInterface
     */
    private TimeIssuerInterface $timeIssuer;

    public function __construct(TimeIssuerInterface $timeIssuer)
    {
        $this->timeIssuer = $timeIssuer;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->setExperimentalOption('prefs', ['intl.accept_languages' => 'RU-ru,ru,en-US,en']);
        $chromeOptions->addArguments(
            [
                '--user-data-dir=/opt/google/chrome/profiles',
            ]
        );
        $driverCapabilities = DesiredCapabilities::chrome();
        $driverCapabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $chromeOptions);
        $driver = RemoteWebDriver::create('http://selenium-hub:4444/wd/hub', $driverCapabilities);

        $browserTabs = $driver->getWindowHandles();

        // stage 1: open site
        if (count($browserTabs) < 2) {
            $this->timeIssuer
                ->getTime()
                ->then(
                    function () use ($driver, &$browserTabs) {
                        $this->timeIssuer->release();

                        $driver->get('https://google.ru/search?q=hardprice+процессоры');

                        $driver->takeScreenshot(PROJECT_DIR . '/tests/_output/stage1a-search-site.png');

                        // moving mouse at random point
                        [$divergenceOffsetX, $divergenceOffsetY] = [random_int(1, 1000), random_int(1, 500)];
                        $driver
                            ->getMouse()
                            ->mouseMove(null, $divergenceOffsetX, $divergenceOffsetY)
                        ;
                        // wait call (async driver port)

                        $pageLink = $driver->findElement(WebDriverBy::xpath('//span[contains(., "category › cpu")]'));

                        [$divergenceOffsetX, $divergenceOffsetY] = [random_int(0, 20), random_int(0, 5)];
                        $pageLinkCoordinates = $pageLink->getCoordinates();
                        $driver
                            ->getMouse()
                            ->mouseMove($pageLinkCoordinates, $divergenceOffsetX, $divergenceOffsetY)
                        ;
                        $driver
                            ->getMouse()
                            ->click()
                        ;
                        $driver->takeScreenshot(PROJECT_DIR . '/tests/_output/stage1a-search-site-clicked.png');

                        $browserTabs = $driver->getWindowHandles();
                    }
                )
            ;
        }

        $hardwareItems  = null;
        $hardwarePrices = [];

        $deferred = new Deferred();

        // stage 2: viewing cpu list
        $this->timeIssuer
            ->getTime()
            ->then(
                function () use ($deferred, $driver, &$browserTabs, &$hardwareItems, &$hardwarePrices) {
                    $this->timeIssuer->release();

                    $currentTab = $driver->getWindowHandle();
                    if ($currentTab !== $browserTabs[0]) {
                        $driver
                            ->switchTo()
                            ->window($browserTabs[0])
                        ;
                    }

                    if ($driver->getCurrentURL() !== 'https://hardprice.ru/media/data/c/cpu.json') {
                        $driver->get('https://hardprice.ru/media/data/c/cpu.json');
                    }
                    $driver->takeScreenshot(PROJECT_DIR . '/tests/_output/stage2-viewing-cpu-list.png');

                    $responseBody   = $driver->getPageSource();
                    $jsonStartIndex = strpos($responseBody, '[');
                    $jsonEndIndex   = strrpos($responseBody, ']') - $jsonStartIndex + 1;
                    $json           = substr($responseBody, $jsonStartIndex, $jsonEndIndex);

                    $itemParser = new ItemParser();

                    /** @var Generator $hardwareItems */
                    $hardwareItems = $itemParser->parse($json);

                    // stage 3: browsing site
                    if ($hardwareItems->valid()) {
                        $this->browseRecursive($deferred, $driver, $browserTabs, $hardwareItems, $hardwarePrices);
                    }
                }
            )
        ;

        return $deferred->promise();
    }

    private function browseRecursive(
        Deferred $deferred,
        RemoteWebDriver $driver,
        &$browserTabs,
        &$hardwareItems,
        &$hardwarePrices
    ) {
        $this->timeIssuer
            ->getTime()
            ->then(
                function () use ($deferred, $driver, &$browserTabs, &$hardwareItems, &$hardwarePrices) {
                    try {
                        $this->timeIssuer->release();

                        $currentTab = $driver->getWindowHandle();
                        if ($currentTab !== $browserTabs[1]) {
                            $driver
                                ->switchTo()
                                ->window($browserTabs[1])
                            ;
                        }
                        $driver->takeScreenshot(PROJECT_DIR . '/tests/_output/stage3a-browsing-site.png');

                        // moving mouse at random point
                        $startingPointCoordinates = $driver
                            ->findElement(
                                WebDriverBy::xpath('//div[contains(@class, "navbar-header")]//a[@href="/"]')
                            )
                            ->getCoordinates()
                        ;
                        [$divergenceOffsetX, $divergenceOffsetY] = [random_int(1, 1000), random_int(1, 500)];
                        $driver
                            ->getMouse()
                            ->mouseMove($startingPointCoordinates, $divergenceOffsetX, $divergenceOffsetY)
                        ;
                        // wait call (async driver port)

                        $storeMap    = [
                            1  => 'regard',
                            3  => 'citilink',
                            4  => 'pleer',
                            5  => 'computeruniverse',
                            6  => 'dns',
                            8  => 'oldi',
                            9  => 'fcenter',
                            11 => 'one123',
                            12 => 'ogo',
                            13 => 'just',
                            15 => 'notik',
                            16 => 'samsung',
                            18 => 'mvideo',
                            20 => 'ozon',
                            26 => 'beeline',
                            27 => 'mts',
                            28 => 'megafon',
                            29 => 'xiaomi',
                            32 => 'kotofoto',
                            36 => 'svyaznoy',
                            38 => 'icover',
                            42 => 'cstore',
                            43 => 'huawei',
                            45 => 'becompact',
                            46 => 'kcentr',
                            47 => 'somebox',
                            48 => 'technopark',
                            49 => 'eldorado',
                            55 => 'biggeek',
                            57 => 'beru',
                            59 => 'mta_ua',
                            60 => 'f_ua',
                            61 => 'ktc_ua',
                            62 => 'moyo_ua',
                            63 => 'rozetka_ua',
                            64 => 'wite',
                            68 => 'xcomshop',
                        ];
                        $priceParser = new PriceParser(new PriceParser\Tokenizer(), new ArrayMapper($storeMap));

                        /** @var Item $item */
                        $item        = $hardwareItems->current();
                        $itemName    = $item->getName();
                        $itemPageUri = $item->getPageUri();

                        // searching an item using the search form.
                        $searchBar = $driver->findElement(
                            WebDriverBy::xpath('//form[@name="search"]//input[@type="text"]')
                        );

                        // moving mouse to the search bar and clicking
                        [$divergenceOffsetX, $divergenceOffsetY] = [random_int(0, 20), random_int(0, 5)];
                        $searchBarCoordinates = $searchBar->getCoordinates();
                        $driver
                            ->getMouse()
                            ->mouseMove($searchBarCoordinates, $divergenceOffsetX, $divergenceOffsetY)
                        ;
                        $driver
                            ->getMouse()
                            ->click()
                        ;

                        $searchTextToType = mb_strtolower($itemName);
                        $driver
                            ->getKeyboard()
                            ->sendKeys($searchTextToType)
                        ;
                        // wait call (async driver port)

                        // waiting for ajax request with search results.
                        $linkXPath = sprintf('//a[@href="%s"]', $itemPageUri);
                        $pageLink  = $driver
                            ->wait()
                            ->until(
                                WebDriverExpectedCondition::visibilityOfElementLocated(
                                    WebDriverBy::xpath($linkXPath)
                                )
                            )
                        ;

                        $screenshotName = sprintf('/tests/_output/stage3b-item-search-%s.png', $itemName);
                        $driver->takeScreenshot(PROJECT_DIR . $screenshotName);

                        // clicking a link in the search results
                        [$divergenceOffsetX, $divergenceOffsetY] = [random_int(0, 20), random_int(0, 5)];
                        $pageLinkCoordinates = $pageLink->getCoordinates();
                        $driver
                            ->getMouse()
                            ->mouseMove($pageLinkCoordinates, $divergenceOffsetX, $divergenceOffsetY)
                        ;
                        $driver
                            ->getMouse()
                            ->click()
                        ;
                        // wait call (async driver port)

                        // ensure page with item is completely loaded.
                        $presenceCheckElementXPath = '//table[contains(@class, "price-all")]//*[@data-store]';
                        $driver
                            ->wait()
                            ->until(
                                WebDriverExpectedCondition::presenceOfElementLocated(
                                    WebDriverBy::xpath($presenceCheckElementXPath)
                                )
                            )
                        ;

                        $screenshotName = sprintf('/tests/_output/stage3n-browsing-site-%s.png', $itemName);
                        $driver->takeScreenshot(PROJECT_DIR . $screenshotName);

                        // parsing page source
                        $responseBody           = $driver->getPageSource();
                        $responseBodyNormalized = preg_replace('/\s+/', '', $responseBody);
                        $priceIterator          = $priceParser->parse($responseBodyNormalized);
                        $prices                 = iterator_to_array($priceIterator);
                        foreach ($prices as $price) {
                            $price->setHardwareName($itemName);
                        }

                        $itemIdentifier                  = $item->getIdentifier();
                        $hardwarePrices[$itemIdentifier] = $prices;

                        $hardwareItems->next();
                        if ($hardwareItems->valid()) {
                            $this->browseRecursive($deferred, $driver, $browserTabs, $hardwareItems, $hardwarePrices);
                        } else {
                            $driver->quit();
                            $deferred->resolve($hardwarePrices);
                        }
                    } catch (Throwable $exception) {
                        $deferred->reject($exception);
                    }
                }
            )
        ;
    }
}
