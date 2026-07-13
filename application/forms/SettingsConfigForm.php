<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Forms;

use Icinga\Module\Totp\Common\Totp;
use Icinga\Web\Form\ConfigForm;
use ipl\Stdlib\Str;
use ipl\Validator\BetweenValidator;
use ipl\Validator\CallbackValidator;
use ipl\Validator\RegexMatchValidator;

class SettingsConfigForm extends ConfigForm
{
    protected function assemble(): void
    {
        $this->addElement('text', 'settings__issuer', [
            'label'       => $this->translate('Issuer'),
            'description' => $this->translate('Name shown in authenticator applications'),
            'required'    => true,
            'validators'  => [new CallbackValidator(function (string $value, CallbackValidator $validator): bool {
                if (Str::isEmpty($value)) {
                    $validator->addMessage($this->translate('Issuer must not be empty'));

                    return false;
                }

                return true;
            })],
            'value'       => Totp::DEFAULT_ISSUER,
        ]);

        $this->addElement('number', 'settings__leeway', [
            'label'       => $this->translate('Leeway'),
            'description' => $this->translate('Allowed clock drift in seconds'),
            'min'         => Totp::MIN_LEEWAY,
            'max'         => Totp::MAX_LEEWAY,
            'required'    => true,
            'validators'  => [
                new RegexMatchValidator([
                    'pattern'         => '/^\d+$/',
                    'notMatchMessage' => $this->translate('Leeway must be a whole number'),
                ]),
                new BetweenValidator(['min' => Totp::MIN_LEEWAY, 'max' => Totp::MAX_LEEWAY, 'inclusive' => true]),
            ],
            'value'       => Totp::DEFAULT_LEEWAY,
        ]);
    }
}
