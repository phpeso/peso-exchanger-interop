<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Interop\Exchanger;

use Arokettu\Date\Calendar;
use Exchanger\Contract\ExchangeRate;
use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\Contract\ExchangeRateService;
use Exchanger\Contract\HistoricalExchangeRateQuery;
use Exchanger\Exception\UnsupportedCurrencyPairException;
use Override;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ExchangeRateResponse;
use Peso\Core\Services\PesoServiceInterface;

require __DIR__ . '/check_exchanger.php';

final readonly class ExchangerService implements ExchangeRateService
{
    public function __construct(
        private PesoServiceInterface $service,
    ) {
    }

    #[Override]
    public function getExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        $result = $this->service->send($this->buildQuery($exchangeQuery));
        if ($result instanceof ExchangeRateResponse) {
            return new \Exchanger\ExchangeRate(
                $exchangeQuery->getCurrencyPair(),
                (float)$result->rate->value,
                $result->date->toDateTime(),
                $this->getName(),
            );
        }
        throw new UnsupportedCurrencyPairException($exchangeQuery->getCurrencyPair(), $this);
    }

    #[Override]
    public function supportQuery(ExchangeRateQuery $exchangeQuery): bool
    {
        return $this->service->supports($this->buildQuery($exchangeQuery));
    }

    private function buildQuery(
        ExchangeRateQuery $exchangeQuery,
    ): CurrentExchangeRateRequest|HistoricalExchangeRateRequest {
        $pair = $exchangeQuery->getCurrencyPair();

        if ($exchangeQuery instanceof HistoricalExchangeRateQuery) {
            return new HistoricalExchangeRateRequest(
                $pair->getBaseCurrency(),
                $pair->getQuoteCurrency(),
                Calendar::fromDateTime($exchangeQuery->getDate()),
            );
        } else {
            return new CurrentExchangeRateRequest(
                $pair->getBaseCurrency(),
                $pair->getQuoteCurrency(),
            );
        }
    }

    #[Override]
    public function getName(): string
    {
        return get_debug_type($this->service);
    }
}
