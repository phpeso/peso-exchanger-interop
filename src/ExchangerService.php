<?php

namespace Peso\Exchanger\Interop;

use Arokettu\Date\Calendar;
use Exchanger\Contract\ExchangeRate;
use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\Contract\ExchangeRateService;
use Exchanger\Contract\HistoricalExchangeRateQuery;
use Exchanger\Exception\UnsupportedCurrencyPairException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\SuccessResponse;
use Peso\Core\Services\ExchangeRateServiceInterface;

final readonly class ExchangerService implements ExchangeRateService
{
    public function __construct(
        private ExchangeRateServiceInterface $service,
    ) {
    }

    public function getExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        $result = $this->service->send($this->buildQuery($exchangeQuery));
        if ($result instanceof SuccessResponse) {
            return new \Exchanger\ExchangeRate(
                $exchangeQuery->getCurrencyPair(),
                (float)$result->rate->value,
                new \DateTimeImmutable(),
                $this->getName(),
            );
        }
        throw new UnsupportedCurrencyPairException($exchangeQuery->getCurrencyPair(), $this);
    }

    public function supportQuery(ExchangeRateQuery $exchangeQuery): bool
    {
        return $this->service->supports($this->buildQuery($exchangeQuery));
    }

    private function buildQuery(
        ExchangeRateQuery $exchangeQuery
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

    public function getName(): string
    {
        return get_debug_type($this->service);
    }
}
