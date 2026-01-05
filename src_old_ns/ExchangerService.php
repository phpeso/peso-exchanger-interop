<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Exchanger\Interop;

class_alias(\Peso\Interop\Exchanger\ExchangerService::class, ExchangerService::class);

if (false) {
    /**
     * @deprecated use \Peso\Interop\Exchanger\ExchangerService
     */
    final readonly class ExchangerService extends \Peso\Interop\Exchanger\ExchangerService
    {
    }
}
