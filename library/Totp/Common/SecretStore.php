<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use Icinga\Web\Session\Session;
use Icinga\Web\Session\SessionNamespace;

/**
 * In-session state for pending TOTP enrollment secrets
 */
class SecretStore
{
    /** @var string Session namespace name to store TOTP secret temporarily */
    protected const SESSION_NAMESPACE = 'totp';

    /** @var SessionNamespace Session namespace scoping the secret store */
    protected SessionNamespace $session;

    /**
     * Create a new SecretStore
     *
     * @param Session $session Session to scope the secret store within
     */
    public function __construct(Session $session)
    {
        $this->session = $session->getNamespace(static::SESSION_NAMESPACE);
    }

    /**
     * Store a pending TOTP enrollment secret
     *
     * @param string $id Enrollment secret id rendered into the form
     * @param string $secret TOTP secret generated on the server
     *
     * @return $this
     */
    public function storeSecret(string $id, string $secret): static
    {
        $this->session->set($id, $secret);

        return $this;
    }

    /**
     * Get a pending TOTP enrollment secret by id
     *
     * @param string $id Enrollment secret id submitted with the form
     *
     * @return ?string The pending TOTP secret, or null if the id is unknown
     */
    public function getSecret(string $id): ?string
    {
        return $this->session->get($id);
    }

    /**
     * Clear all pending enrollment secrets for this session
     *
     * A successful enrollment makes other open enrollment tabs obsolete. Clear
     * the whole store so older QR codes cannot be submitted afterward.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session->clear();
    }
}
