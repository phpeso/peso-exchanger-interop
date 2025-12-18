<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

use Exchanger\Contract\ExchangeRateService;

if (!interface_exists(ExchangeRateService::class)) {
    throw new Error(
        // phpcs:ignore Generic.Files.LineLength.TooLong
        'florianv/exchanger v2.x or a compatible fork like part-db/exchanger v3.x needs to be installed to use peso/peso-exchanger-interop',
    );
}
