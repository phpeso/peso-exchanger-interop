<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Exchanger\Interop;

use Arokettu\Date\Calendar;
use Arokettu\Date\Date;
use DateInterval;
use Exchanger\Contract\ExchangeRateService;
use Exchanger\Exception\UnsupportedCurrencyPairException;
use Exchanger\ExchangeRateQuery;
use Exchanger\ExchangeRateQueryBuilder;
use Override;
use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\ExchangeRateResponse;
use Peso\Core\Services\PesoServiceInterface;
use Peso\Core\Services\SDK\Cache\NullCache;
use Peso\Core\Types\Decimal;
use Psr\SimpleCache\CacheInterface;

require __DIR__ . '/check_exchanger.php';

final readonly class PesoService implements PesoServiceInterface
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

    #[Override]
    public function send(object $request): ExchangeRateResponse|ErrorResponse
    {
        if (!$request instanceof CurrentExchangeRateRequest && !$request instanceof HistoricalExchangeRateRequest) {
            return new ErrorResponse(RequestNotSupportedException::fromRequest($request));
        }

        $cacheKey = 'peso|swap|' . hash('sha1', $this->cachePrefix . serialize($request));

        $data = $this->cache->get($cacheKey);
        if ($data) {
            return new ExchangeRateResponse(new Decimal($data['rate']), Date::createFromJulianDay($data['date']));
        }

        try {
            $result = $this->service->getExchangeRate($this->buildQuery($request));
            $data = [
                'rate' => $rate = (string)$result->getValue(),
                'date' => ($date = Calendar::fromDateTime($result->getDate()))->julianDay,
            ];
            $this->cache->set($cacheKey, $data, $this->ttl);
            return new ExchangeRateResponse(new Decimal($rate), $date);
        } catch (UnsupportedCurrencyPairException) {
            return new ErrorResponse(ExchangeRateNotFoundException::fromRequest($request));
        }
    }

    #[Override]
    public function supports(object $request): bool
    {
        if (!$request instanceof CurrentExchangeRateRequest && !$request instanceof HistoricalExchangeRateRequest) {
            return false;
        }

        return $this->service->supportQuery($this->buildQuery($request));
    }
}
