<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use ipl\Sql\Select;
use OTPHP\TOTP as TotpLib;

/**
 * TOTP implementation wrapping the OTPHP library
 */
class Totp implements TotpInterface
{
    /** @var string Issuer name shown in authenticator apps to identify this service */
    protected const ISSUER = 'icingaweb2';

    /** @var int Allowed clock drift in seconds accepted on either side of the current time */
    protected const LEEWAY = 10;

    /** @var int Size of the secret in bytes */
    protected const SECRET_SIZE = 20;

    protected TotpLib $totp;

    /**
     * Create a new Totp
     *
     * When $totp is omitted, a new secret of {@see SECRET_SIZE} bytes is generated.
     *
     * @param ?TotpLib $totp Underlying TOTP instance. A new one is generated when null.
     */
    public function __construct(?TotpLib $totp = null)
    {
        $this->totp = $totp ?? TotpLib::generate(new PsrClock(), static::SECRET_SIZE);
    }

    public function getSecret(): string
    {
        return $this->totp->getSecret();
    }

    public function getUrl(string $username): string
    {
        return $this->totp
            ->withIssuer(static::ISSUER)
            ->withLabel($username)
            ->getProvisioningUri();
    }

    /**
     * Check whether a TOTP token is valid
     *
     * A token is valid for up to {@see LEEWAY} seconds before or after the current time
     * to accommodate clock drift.
     *
     * @param string $token The token to verify
     *
     * @return bool
     */
    public function verify(string $token): bool
    {
        return $this->totp->verify($token, null, static::LEEWAY);
    }

    public static function fromSecret(string $secret): static
    {
        return new static(TotpLib::createFromSecret($secret, new PsrClock()));
    }

    public static function fromDb(string $username): ?static
    {
        $select = (new Select())
            ->from('secret')
            ->columns('secret')
            ->where(['username = ?' => $username]);

        $dbTotp = Database::connection()->select($select)->fetch();

        if (! $dbTotp) {
            return null;
        }

        return static::fromSecret($dbTotp->secret);
    }
}
