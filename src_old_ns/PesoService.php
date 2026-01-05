<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Exchanger\Interop;

class_alias(\Peso\Interop\Exchanger\PesoService::class, PesoService::class);

if (false) {
    /**
     * @deprecated use \Peso\Interop\Exchanger\PesoService
     */
    final readonly class PesoService extends \Peso\Interop\Exchanger\PesoService
    {
    }
}
