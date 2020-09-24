<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Util\UrlValidator;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $urlValidator;
    private ObjectProphecy $httpClient;
    private UrlShortenerOptions $options;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->options = new UrlShortenerOptions(['validate_url' => true]);
        $this->urlValidator = new UrlValidator($this->httpClient->reveal(), $this->options);
    }

    /** @test */
    public function exceptionIsThrownWhenUrlIsInvalid(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willThrow(ClientException::class);

        $request->shouldBeCalledOnce();
        $this->expectException(InvalidUrlException::class);

        $this->urlValidator->validateUrl('http://foobar.com/12345/hello?foo=bar', null);
    }

    /** @test */
    public function expectedUrlIsCalledWhenTryingToVerify(): void
    {
        $expectedUrl = 'http://foobar.com';

        $request = $this->httpClient->request(
            RequestMethodInterface::METHOD_GET,
            $expectedUrl,
            [
                RequestOptions::ALLOW_REDIRECTS => ['max' => 15],
                RequestOptions::IDN_CONVERSION => true,
            ],
        )->willReturn(new Response());

        $this->urlValidator->validateUrl($expectedUrl, null);

        $request->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideDisabledCombinations
     */
    public function noCheckIsPerformedWhenUrlValidationIsDisabled(?bool $doValidate, bool $validateUrl): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn(new Response());
        $this->options->validateUrl = $validateUrl;

        $this->urlValidator->validateUrl('', $doValidate);

        $request->shouldNotHaveBeenCalled();
    }

    public function provideDisabledCombinations(): iterable
    {
        yield 'config is disabled and no runtime option is provided' => [null, false];
        yield 'config is enabled but runtime option is disabled' => [false, true];
        yield 'both config and runtime option are disabled' => [false, false];
    }
}
