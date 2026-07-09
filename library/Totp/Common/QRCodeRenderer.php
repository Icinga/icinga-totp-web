<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use chillerlan\QRCode\QRCode;

/**
 * QR code SVG data URI renderer backed by chillerlan/php-qrcode
 */
class QRCodeRenderer implements QRCodeRendererInterface
{
    public function render(string $data): string
    {
        return (new QRCode())->render($data);
    }
}
