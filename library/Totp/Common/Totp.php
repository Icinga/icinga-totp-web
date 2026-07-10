<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use Icinga\Application\Config;
use ipl\Sql\Select;
use ipl\Stdlib\Str;
use OTPHP\TOTP as TotpLib;

/**
 * TOTP implementation wrapping the OTPHP library
 */
class Totp implements TotpInterface
{
    /** @var string Issuer name shown in authenticator apps to identify this service */
    public const DEFAULT_ISSUER = 'icingaweb';

    /** @var int Allowed clock drift in seconds accepted on either side of the current time */
    public const DEFAULT_LEEWAY = 10;

    /** @var int Minimum allowed clock drift in seconds */
    public const MIN_LEEWAY = 0;

    /** @var int Maximum allowed clock drift in seconds */
    public const MAX_LEEWAY = 29;

    /** @var int Size of the secret in bytes */
    protected const SECRET_SIZE = 20;

    protected TotpLib $totp;

    /** @var string Issuer name shown in authenticator apps */
    protected string $issuer;

    /** @var int Allowed clock drift in seconds */
    protected int $leeway;

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

        $settings = Config::module('totp')->getSection('settings');
        $configuredIssuer = $settings->get('issuer');
        $configuredLeeway = filter_var($settings->get('leeway'), FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => static::MIN_LEEWAY,
                'max_range' => static::MAX_LEEWAY,
            ],
        ]);

        $this->issuer = Str::isEmpty($configuredIssuer) ? static::DEFAULT_ISSUER : $configuredIssuer;
        $this->leeway = $configuredLeeway !== false ? $configuredLeeway : static::DEFAULT_LEEWAY;
    }

    public function getSecret(): string
    {
        return $this->totp->getSecret();
    }

    public function getUrl(string $username): string
    {
        return $this->totp
            ->withIssuer($this->issuer)
            ->withLabel($username)
            ->getProvisioningUri();
    }

    /**
     * Check whether a TOTP token is valid
     *
     * A token is valid for up to the configured leeway before or after the current time
     * to accommodate clock drift.
     *
     * @param string $token The token to verify
     *
     * @return bool
     */
    public function verify(string $token): bool
    {
        return $this->totp->verify($token, null, $this->leeway);
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
