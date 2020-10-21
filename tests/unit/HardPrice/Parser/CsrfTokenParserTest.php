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

namespace Sterlett\Tests\HardPrice\Parser;

use PHPUnit\Framework\TestCase;
use Sterlett\HardPrice\Parser\CsrfTokenParser;

/**
 * Tests CSRF token parsing for HardPrice website
 */
final class CsrfTokenParserTest extends TestCase
{
    /**
     * Extracts a CSRF token from the website page content
     *
     * @var CsrfTokenParser
     */
    private CsrfTokenParser $csrfTokenParser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->csrfTokenParser = new CsrfTokenParser();
    }

    /**
     * Ensures CSRF token is properly parsed from the expected response contents
     *
     * @return void
     */
    public function testCsrfTokenIsParsed(): void
    {
        $responseBodyContents = <<<HTML_CHUNK
            ...
                <p>HardPrice v1.1.21
                &nbsp; <a href="https://vk.com/hardprice" target="_blank" rel="nofollow noopener"><i class="fa fa-vk help" style="color:#45668e"></i> Группа вконтакте</a>
                &nbsp; <a href="https://www.youtube.com/channel/UC5xNcu8z-GMXyFgd9yJOtJA" target="_blank" rel="nofollow noopener"><i class="fa fa-youtube-play" style="color:#45668e"></i> Youtube</a>
                &nbsp; <a href="https://t.me/hardprice" target="_blank" rel="nofollow noopener"><i class="fa fa-telegram help" style="color:#45668e"></i> Telegram</a>
                &nbsp; <a href="https://twitter.com/HardPrice" target="_blank" rel="nofollow noopener"><i class="fa fa-twitter help" style="color:#45668e"></i> Твиттер</a>
                
                </p>
            
                <p>&nbsp;</p>
            </div>

            <script>
                var app = $.extend(true, {}, {
                token: 'a40321e12a97e493c588f711816f55c904907642', categoryId: 0,
                auth:{user:{id:0}}
                }, window.app);
            </script>
            
            <script src="/assets/all.min.js?v=1.1.21" type="text/javascript"></script>

            <script type="text/javascript">
                app.euroRate =  parseFloat('91.45');
                app.currencies[2].value = parseFloat('77.95');
                app.currencies[3].value = parseFloat('91.45');
                app.currencies[4].value = parseFloat('2.75');
                app.region.current = {"id":"1","title":"\u041c\u043e\u0441\u043a\u0432\u0430","slug":"RU_ALL","countryId":1,"tag":"MSK"};
            </script>
HTML_CHUNK;

        $csrfTokenExpected = 'a40321e12a97e493c588f711816f55c904907642';
        $csrfTokenActual   = $this->csrfTokenParser->parse($responseBodyContents);

        $this->assertEquals($csrfTokenExpected, $csrfTokenActual, 'CSRF token is not properly parsed.');
    }
}
