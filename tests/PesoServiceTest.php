<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Interop\Exchanger\Tests;

use Arokettu\Date\Calendar;
use Arokettu\Date\Date;
use Exchanger\Service\PhpArray;
use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\ExchangeRateResponse;
use Peso\Interop\Exchanger\PesoService;
use SlevomatCodingStandard\Sniffs\TestCase;
use stdClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

use function Arokettu\Debug\set_private_field;

final class PesoServiceTest extends TestCase
{
    public function testRate(): void
    {
        $exchange = new PhpArray([
            'EUR/USD' => '1.1234',
        ], [
            '2025-06-13' => [
                'EUR/USD' => '1.2345',
            ],
        ]);
        $cache = new Psr16Cache(new ArrayAdapter());

        $service = new PesoService($exchange, $cache);

        $dateBefore = Date::today(); // for a case when date changes when test is running
        $response = $service->send(new CurrentExchangeRateRequest('EUR', 'USD'));
        $dateAfter = Date::today();

        self::assertInstanceOf(ExchangeRateResponse::class, $response);
        self::assertEquals('1.1234', $response->rate->value);
        self::assertGreaterThanOrEqual($dateBefore->julianDay, $response->date->julianDay);
        self::assertLessThanOrEqual($dateAfter->julianDay, $response->date->julianDay);

        // retrieve the same thing from a cache

        set_private_field($exchange, 'latestRates', []); // make sure we're reading from the cache

        $dateBefore = Date::today(); // for a case when date changes when test is running
        $response = $service->send(new CurrentExchangeRateRequest('EUR', 'USD'));
        $dateAfter = Date::today();

        self::assertInstanceOf(ExchangeRateResponse::class, $response);
        self::assertEquals('1.1234', $response->rate->value);
        self::assertGreaterThanOrEqual($dateBefore->julianDay, $response->date->julianDay);
        self::assertLessThanOrEqual($dateAfter->julianDay, $response->date->julianDay);

        // historical

        $response = $service->send(
            new HistoricalExchangeRateRequest('EUR', 'USD', Calendar::parse('2025-06-13')),
        );

        self::assertInstanceOf(ExchangeRateResponse::class, $response);
        self::assertEquals('1.2345', $response->rate->value);
        self::assertEquals('2025-06-13', $response->date->toString());

        // unsupported pair

        $response = $service->send(new CurrentExchangeRateRequest('USD', 'EUR'));

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ExchangeRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for USD/EUR', $response->exception->getMessage());
    }

    public function testUnknownRequest(): void
    {
        $exchange = new PhpArray([
            'EUR/USD' => '1.1234',
        ], [
            '2025-06-13' => [
                'EUR/USD' => '1.2345',
            ],
        ]);

        $service = new PesoService($exchange);

        $response = $service->send(new stdClass());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
        self::assertEquals('Unsupported request type: "stdClass"', $response->exception->getMessage());
    }

    public function testSupport(): void
    {
        $exchange = new PhpArray([
            'EUR/USD' => '1.1234',
        ], [
            '2025-06-13' => [
                'EUR/USD' => '1.2345',
            ],
        ]);

        $service = new PesoService($exchange);

        self::assertTrue($service->supports(new CurrentExchangeRateRequest('EUR', 'USD')));
        self::assertFalse($service->supports(new CurrentExchangeRateRequest('USD', 'EUR')));
        self::assertTrue($service->supports(
            new HistoricalExchangeRateRequest('EUR', 'USD', Calendar::parse('2025-06-13')),
        ));
        self::assertFalse($service->supports(new stdClass()));
    }
}
