<?php

namespace Peso\Exchanger\Interop;

use Arokettu\Date\Calendar;
use Arokettu\Date\Date;
use DateInterval;
use Exchanger\Contract\ExchangeRateService;
use Exchanger\Exception\UnsupportedCurrencyPairException;
use Exchanger\ExchangeRateQuery;
use Exchanger\ExchangeRateQueryBuilder;
use Peso\Core\Exceptions\ConversionRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\SuccessResponse;
use Peso\Core\Services\ExchangeRateServiceInterface;
use Peso\Core\Services\SDK\Cache\NullCache;
use Peso\Core\Types\Decimal;
use Psr\SimpleCache\CacheInterface;

final readonly class PesoService implements ExchangeRateServiceInterface
{
    private string $cachePrefix;

    public function __construct(
        private ExchangeRateService $service,
        private CacheInterface $cache = new NullCache(),
        private DateInterval $ttl = new DateInterval('PT1H'),
        string|null $cachePrefix = null,
    ) {
        $this->cachePrefix = $cachePrefix ?? get_debug_type($this->service);
    }

    private function buildQuery(
        CurrentExchangeRateRequest|HistoricalExchangeRateRequest $request,
    ): ExchangeRateQuery {
        $builder = new ExchangeRateQueryBuilder(\sprintf(
            '%s/%s',
            $request->baseCurrency,
            $request->quoteCurrency,
        ));

        if ($request instanceof HistoricalExchangeRateRequest) {
            $builder->setDate($request->date->toDateTime());
        }

        return $builder->build();
    }

    public function send(object $request): SuccessResponse|ErrorResponse
    {
        if (!$request instanceof CurrentExchangeRateRequest && !$request instanceof HistoricalExchangeRateRequest) {
            return new ErrorResponse(RequestNotSupportedException::fromRequest($request));
        }

        $cacheKey = hash('sha1', $this->cachePrefix . serialize($request));

        $data = $this->cache->get($cacheKey);
        if ($data) {
            return new SuccessResponse(new Decimal($data['rate']), Date::createFromJulianDay($data['date']));
        }

        try {
            $result = $this->service->getExchangeRate($this->buildQuery($request));
            $data = [
                'rate' => $rate = (string)$result->getValue(),
                'date' => ($date = Calendar::fromDateTime($result->getDate()))->julianDay,
            ];
            $this->cache->set($cacheKey, $data, $this->ttl);
            return new SuccessResponse(new Decimal($rate), $date);
        } catch (UnsupportedCurrencyPairException) {
            return new ErrorResponse(ConversionRateNotFoundException::fromRequest($request));
        }
    }

    public function supports(object $request): bool
    {
        if (!$request instanceof CurrentExchangeRateRequest && !$request instanceof HistoricalExchangeRateRequest) {
            return false;
        }

        return $this->service->supportQuery($this->buildQuery($request));
    }
}
