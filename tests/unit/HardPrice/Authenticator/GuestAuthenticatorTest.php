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

namespace Sterlett\Tests\HardPrice\Authenticator;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\StreamSelectLoop;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Sterlett\ClientInterface;
use Sterlett\HardPrice\Authentication;
use Sterlett\HardPrice\Authenticator\GuestAuthenticator;
use Sterlett\HardPrice\Csrf\TokenParser as CsrfTokenParser;
use Sterlett\HardPrice\SessionMemento;
use Throwable;
use function Clue\React\Block\await;
use function React\Promise\resolve;

/**
 * Tests guest authentication for HardPrice website
 */
final class GuestAuthenticatorTest extends TestCase
{
    /**
     * Performs authentication for the subsequent requests to mimic guest activity
     *
     * @var GuestAuthenticator
     */
    private GuestAuthenticator $guestAuthenticator;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $timeIssuerMock = $this->createMock(TimeIssuerInterface::class);
        $timeIssuerMock
            ->expects($this->atLeastOnce())
            ->method('getTime')
            ->willReturn(resolve(null))
        ;

        // building http client context.
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with($this->equalTo('set-cookie'))
            ->willReturn(
                'hardprice=6frrvbqtplib5dpuqakcvn51nb; expires=Thu, 22-Oct-2020 23:47:04 GMT; Max-Age=604800; '
                . 'path=/; secure; HttpOnly'
            )
        ;

        $responseBodyStreamMock = $this->createMock(StreamInterface::class);
        $responseBodyStreamMock
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('body contents stub')
        ;

        $responseMock
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($responseBodyStreamMock)
        ;

        $responsePromiseResolved = resolve($responseMock);

        $httpClientMock = $this->createMock(ClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($responsePromiseResolved)
        ;

        $sessionMemento = new SessionMemento();

        $csrfTokenParserMock = $this->createMock(CsrfTokenParser::class);
        $csrfTokenParserMock
            ->expects($this->once())
            ->method('parse')
            ->with($this->equalTo('body contents stub'))
            ->willReturn('ec35943e8108de53264c982b6a9450c82f6d71b9')
        ;

        $authenticationUri = 'uri';

        $this->guestAuthenticator = new GuestAuthenticator(
            $timeIssuerMock,
            $sessionMemento,
            $httpClientMock,
            $csrfTokenParserMock,
            $authenticationUri
        );
    }

    /**
     * Ensures that authenticate method's output resolves to the valid authentication object
     *
     * @return void
     */
    public function testAuthenticateCallMakesValidContext(): void
    {
        $authenticationPromise = $this->guestAuthenticator->authenticate('authentication.uri_path');
        $authentication        = null;

        try {
            $authentication = await($authenticationPromise, new StreamSelectLoop(), 1.0);
        } catch (Throwable $rejectionReason) {
            $failMessage = sprintf(
                "An authentication promise after 'authenticate' call has been rejected with a reason: %s",
                (string) $rejectionReason
            );

            $this->fail($failMessage);
        }

        $this->assertInstanceOf(
            Authentication::class,
            $authentication,
            sprintf("Authentication promise must be resolved to the instance of '%s'.", Authentication::class)
        );

        $cookiesExpected = ['hardprice=6frrvbqtplib5dpuqakcvn51nb'];
        $cookiesActual   = $authentication->getCookies();

        $this->assertEqualsCanonicalizing($cookiesExpected, $cookiesActual, 'Cookies are not properly extracted.');

        $csrfTokenExpected = 'ec35943e8108de53264c982b6a9450c82f6d71b9';
        $csrfTokenActual   = $authentication->getCsrfToken();

        $this->assertEquals(
            $csrfTokenExpected,
            $csrfTokenActual,
            'CSRF Token is not properly extracted.'
        );
    }

    // todo: test for session memento
}
