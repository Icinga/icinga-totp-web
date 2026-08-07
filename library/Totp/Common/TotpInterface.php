<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use SensitiveParameter;

/**
 * Contract for TOTP secret management and token verification
 */
interface TotpInterface
{
    /**
     * Get the base32-encoded TOTP secret
     *
     * @return string
     */
    public function getSecret(): string;

    /**
     * Get the `otpauth://totp/` provisioning URI for the TOTP secret
     *
     * @param string $username Username used as the account label in the provisioning URI
     *
     * @return string
     */
    public function getUrl(string $username): string;

    /**
     * Check whether a TOTP token is valid
     *
     * @param string $token The token to verify
     *
     * @return bool
     */
    public function verify(#[SensitiveParameter] string $token): bool;

    /**
     * Create an instance from an existing base32-encoded secret
     *
     * @param string $secret Base32-encoded TOTP secret
     *
     * @return static
     */
    public static function fromSecret(string $secret): static;

    /**
     * Create an instance from the secret stored in the database for a given user
     *
     * @param string $username Username to look up
     *
     * @return ?static Null if no secret is stored for the user
     */
    public static function fromDb(string $username): ?static;
}
