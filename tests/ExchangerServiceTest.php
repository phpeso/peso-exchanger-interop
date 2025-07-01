<?php

declare(strict_types=1);

namespace Peso\Exchanger\Interop\Tests;

use Arokettu\Date\Calendar;
use Arokettu\Date\Date;
use DateTimeImmutable;
use Exchanger\CurrencyPair;
use Exchanger\Exception\UnsupportedCurrencyPairException;
use Exchanger\Exchanger;
use Exchanger\ExchangeRateQueryBuilder;
use Peso\Core\Services\ArrayService;
use Peso\Exchanger\Interop\ExchangerService;
use PHPUnit\Framework\TestCase;

final class ExchangerServiceTest extends TestCase
{
    public function testRate(): void
    {
        $service = new ArrayService([
            'EUR' => ['USD' => '1.1234'],
        ], [
            '2025-06-13' => [
                'EUR' => ['USD' => '1.2345'],
            ],
        ]);

        $exchanger = @new Exchanger(new ExchangerService($service));

        $query = (new ExchangeRateQueryBuilder('EUR/USD'))
            ->build();

        $dateBefore = Date::today()->toDateTime();
        $rate = $exchanger->getExchangeRate($query);
        $dateAfter = Date::today()->toDateTime();

        self::assertEquals('1.1234', $rate->getValue());
        self::assertEquals(new CurrencyPair('EUR', 'USD'), $rate->getCurrencyPair());
        self::assertGreaterThanOrEqual($dateBefore, $rate->getDate());
        self::assertGreaterThanOrEqual($dateAfter, $rate->getDate());
        self::assertEquals(ArrayService::class, $rate->getProviderName());
    }

    public function testHistoricalRate(): void
    {
        $service = new ArrayService([
            'EUR' => ['USD' => '1.1234'],
        ], [
            '2025-06-13' => [
                'EUR' => ['USD' => '1.2345'],
            ],
        ]);

        $exchanger = @new Exchanger(new ExchangerService($service));

        $query = (new ExchangeRateQueryBuilder('EUR/USD'))
            ->setDate(new DateTimeImmutable('2025-06-13'))
            ->build();

        $rate = $exchanger->getExchangeRate($query);

        self::assertEquals('1.2345', $rate->getValue());
        self::assertEquals(new CurrencyPair('EUR', 'USD'), $rate->getCurrencyPair());
        self::assertEquals(Calendar::parse('2025-06-13')->toDateTime(), $rate->getDate());
        self::assertEquals(ArrayService::class, $rate->getProviderName());
    }

    public function testNoRate(): void
    {
        $service = new ArrayService([
            'EUR' => ['USD' => '1.1234'],
        ], [
            '2025-06-13' => [
                'EUR' => ['USD' => '1.2345'],
            ],
        ]);

        $exchanger = @new Exchanger(new ExchangerService($service));

        $query = (new ExchangeRateQueryBuilder('USD/EUR'))
            ->build();

        self::expectException(UnsupportedCurrencyPairException::class);
        self::expectExceptionMessage(
            'The currency pair "USD/EUR" is not supported by the service "Peso\Exchanger\Interop\ExchangerService".',
        );
        $exchanger->getExchangeRate($query);
    }
}
