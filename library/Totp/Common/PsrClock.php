<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * PSR-20 clock implementation returning the current system time
 */
class PsrClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
