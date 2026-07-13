<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

/**
 * Contract for rendering data as a QR code SVG data URI
 */
interface QRCodeRendererInterface
{
    /**
     * Render data as a QR code SVG data URI
     *
     * @param string $data Data to encode in the QR code
     *
     * @return string The rendered QR code as an SVG data URI
     */
    public function render(string $data): string;
}
