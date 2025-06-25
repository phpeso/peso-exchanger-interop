# Peso and Exchanger Interoperability

[![Packagist]][Packagist Link]
[![PHP]][Packagist Link]
[![License]][License Link]

[Packagist]: https://img.shields.io/packagist/v/peso/peso-exchanger-interop.svg?style=flat-square
[PHP]: https://img.shields.io/packagist/php-v/peso/peso-exchanger-interop.svg?style=flat-square
[License]: https://img.shields.io/packagist/l/peso/peso-exchanger-interop.svg?style=flat-square

[Packagist Link]: https://packagist.org/packages/peso/peso-exchanger-interop
[License Link]: LICENSE.md

[Peso Framework] interoperability package for [Exchanger] and [Swap].

[Peso Framework]: https://phpeso.readthedocs.io/
[Exchanger]: https://florianv.github.io/exchanger/
[Swap]: https://florianv.github.io/swap/

## Installation

```bash
composer require peso/peso-exchanger-interop
```

## Example

Peso services in Exchanger:

```php
<?php

use Exchanger\Exchanger;
use Exchanger\ExchangeRateQueryBuilder;
use Peso\Exchanger\Interop\ExchangerService;
use Peso\Services\EuropeanCentralBankService;

$service = new ExchangerService(new EuropeanCentralBankService());
$exchanger = new Exchanger($service);

$query = (new ExchangeRateQueryBuilder('EUR/USD'))
    ->setDate(new DateTimeImmutable('2025-06-13'))
    ->build();

$rate = $exchanger->getExchangeRate($query);

echo $rate->getValue(), PHP_EOL; // 1.1512
```

Exchanger services in Peso:

```php
<?php

use Exchanger\Service\EuropeanCentralBank;
use Peso\Exchanger\Interop\PesoService;
use Peso\Peso\CurrencyConverter;

$service = new PesoService(new EuropeanCentralBank());
$peso = new CurrencyConverter($service);

// 1.1512
echo $peso->getHistoricalConversionRate('EUR', 'USD', '2025-06-13'), PHP_EOL;
```

## Documentation

Read the full documentation here: <https://phpeso.org/v0.x/interop/exchanger.html>

## Support

Please file issues on our main repo at GitHub: <https://github.com/phpeso/peso-exchanger-interop/issues>

## License

The library is available as open source under the terms of the [MIT License][License Link].
