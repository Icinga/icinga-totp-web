<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\ProvidedHook;

use DateTimeImmutable;
use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Module\Totp\Common\Database;
use Icinga\Module\Totp\Common\QRCodeRenderer;
use Icinga\Module\Totp\Common\QRCodeRendererInterface;
use Icinga\Module\Totp\Common\SecretStore;
use Icinga\Module\Totp\Common\Totp;
use Icinga\Module\Totp\Common\TotpInterface;
use Icinga\Module\Totp\Validator\TokenValidator;
use Icinga\User;
use Icinga\Web\Session;
use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Sql\Select;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\DisplayFormElement;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Callout;
use ipl\Web\Widget\CopyToClipboard;
use SensitiveParameter;

/**
 * TOTP-based two-factor authentication hook for Icinga Web
 *
 * Manages the complete TOTP lifecycle: enrollment via QR code scan backed by
 * session-stored pending secrets, per-login token verification against the
 * database-stored secret, and unenrollment.
 */
class TwoFactorTotp extends TwoFactorHook
{
    use Translation;

    /** @var string Hidden form field name carrying the enrollment secret id */
    protected const TOTP_SECRET_ID = 'totp_secret_id';

    /** @var string Form field name for the TOTP verification token */
    protected const TOTP_TOKEN = 'totp_token';

    protected ?TotpInterface $totp = null;

    protected readonly QRCodeRendererInterface $qrRenderer;

    protected bool $enrollmentSessionExpired = false;

    public function __construct()
    {
        $this->qrRenderer = new QRCodeRenderer();
    }

    public function getName(): string
    {
        return 'totp';
    }

    public function getDisplayName(): string
    {
        return 'TOTP';
    }

    public function isEnrolled(User $user): bool
    {
        $select = (new Select())
            ->from('secret')
            ->columns('username')
            ->where(['username = ?' => $user->getUsername()]);

        return Database::connection()->select($select)->fetch() !== false;
    }

    /**
     * Verify a 2FA TOTP token
     *
     * A token is valid for up to the configured leeway before or after the current time
     * to accommodate clock drift.
     *
     * @param User $user The user whose TOTP secret to verify against
     * @param string $token The token to verify
     *
     * @return bool
     */
    public function verify(User $user, #[SensitiveParameter] string $token): bool
    {
        if ($this->totp === null && ($this->totp = Totp::fromDb($user->getUsername())) === null) {
            return false;
        }

        return $this->totp->verify($token);
    }

    public function enroll(User $user, FieldsetElement $fieldset): bool
    {
        $expireMessage = $this->translate('Enrollment session has expired. Please scan the QR code again.');
        $tokenElement = $fieldset->getElement(static::TOTP_TOKEN);

        if ($this->enrollmentSessionExpired) {
            $tokenElement->setValue(null);
            $tokenElement->addMessage($expireMessage);

            return false;
        }

        $secretId = $fieldset->getValue(static::TOTP_SECRET_ID);
        if ($secretId === null) {
            $tokenElement->addMessage($expireMessage);

            return false;
        }

        $secretStore = new SecretStore(Session::getSession());
        $secret = $secretStore->getSecret($secretId);
        if ($secret === null) {
            $tokenElement->addMessage($expireMessage);

            return false;
        }

        $this->totp = Totp::fromSecret($secret);
        $token = $tokenElement->getValue();
        if (! $token || ! $this->verify($user, $token)) {
            $tokenElement->addMessage($this->translate('Token is invalid. Please try again.'));

            return false;
        }

        Database::connection()->prepexec(
            (new Insert())
                ->into('secret')
                ->values([
                    'username' => $user->getUsername(),
                    'secret'   => $this->totp->getSecret(),
                    'ctime'    => (int) (new DateTimeImmutable())->format("Uv"),
                ]),
        );

        $secretStore->clear();

        return true;
    }

    public function unenroll(User $user): void
    {
        Database::connection()->prepexec(
            (new Delete())
                ->from('secret')
                ->where(['username = ?' => $user->getUsername()]),
        );
    }

    public function assembleEnrollmentFormElements(User $user, FieldsetElement $fieldset): void
    {
        $this->enrollmentSessionExpired = false;
        $secretStore = new SecretStore(Session::getSession());
        $secretId = $fieldset->getPopulatedValue(static::TOTP_SECRET_ID);

        if ($secretId === null) {
            $secretId = $this->generateSecretId();
            $this->totp = new Totp();
            $secretStore->storeSecret($secretId, $this->totp->getSecret());
        } elseif ($secret = $secretStore->getSecret($secretId)) {
            // Keep the secret after form submission, otherwise every
            // submission would generate a new secret. Users would have to
            // scan a new QR code after every failed verification, and the
            // token check would fail because the secret changed.
            $this->totp = Totp::fromSecret($secret);
        } else {
            // The submitted secret id is no longer backed by server-side state.
            // Start over with a fresh secret instead of accepting an unknown
            // client-supplied id.
            $this->enrollmentSessionExpired = true;
            $secretId = $this->generateSecretId();
            $fieldset->clearPopulatedValue(static::TOTP_SECRET_ID);
            $this->totp = new Totp();
            $secretStore->storeSecret($secretId, $this->totp->getSecret());
        }

        $fieldset->addHtml(
            new DisplayFormElement(
                new Callout(
                    CalloutType::Warning,
                    $this->translate('Save the QR code or the secret for recovery purposes.'),
                ),
            ),
        );

        $qrCode = $this->createQRCode($user);

        $fieldset->addHtml(new DisplayFormElement(
            HtmlElement::create(
                'img',
                Attributes::create([
                    'class' => 'totp-qr-code',
                    'src'   => $qrCode,
                    'alt'   => $this->translate('QR code for enrolling in TOTP two-factor authentication'),
                ]),
            ),
            $this->translate('QR Code'),
            $this->translate('Use your authenticator app to scan the QR code.'),
        ));

        $fieldset->addHtml(new DisplayFormElement(
            new ActionLink(
                $this->translate('Download QR Code (e.g. for Recovery)'),
                $qrCode,
                'download',
                Attributes::create(['download' => 'icinga-web-totp-qr-code.svg']),
            ),
            description: $this->translate('Download the QR code to back up your two-factor'
                . ' authentication in case you lose access to your device.'),
        ));

        $manualSecret = HtmlElement::create(
            'div',
            Attributes::create(['class' => 'totp-manual-secret']),
            Text::create($this->totp->getSecret()),
        );
        CopyToClipboard::attachTo($manualSecret);
        $fieldset->addHtml(new DisplayFormElement(
            $manualSecret,
            $this->translate('Manual Secret'),
            $this->translate('If you have no camera to scan the QR code you can enter the secret manually.'),
        ));

        $fieldset->addElement('text', static::TOTP_TOKEN, [
            'required'    => true,
            'label'       => $this->translate('Verification Token'),
            'description' => $this->translate(
                'Please enter the token from your authenticator app to verify your setup.',
            ),
            'validators'  => [new TokenValidator()],
        ]);

        $fieldset->addElement('hidden', static::TOTP_SECRET_ID, ['value' => $secretId]);
    }

    /**
     * Create the QR code for the enrollment TOTP secret
     *
     * @param User $user The user being enrolled
     *
     * @return string The rendered QR code as SVG data URI
     */
    protected function createQRCode(User $user): string
    {
        return $this->qrRenderer->render($this->totp->getUrl($user->getUsername()));
    }

    /**
     * Generate a random id for a pending TOTP enrollment secret
     *
     * @return string
     */
    protected function generateSecretId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
