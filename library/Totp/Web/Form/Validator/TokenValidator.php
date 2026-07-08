<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Web\Form\Validator;

use ipl\I18n\Translation;
use ipl\Validator\BaseValidator;

/**
 * Validates that a value consists of exactly the required number of decimal digits
 */
class TokenValidator extends BaseValidator
{
    use Translation;

    /**
     * Create a new TokenValidator
     *
     * @param int $digits Number of decimal digits the token must contain
     */
    public function __construct(
        /** @var int Token length */
        protected int $digits = 6,
    ) {
    }

    public function isValid(mixed $value): bool
    {
        $value = (string) $value;

        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if (! ctype_digit($value)) {
            $this->addMessage($this->translate('The token must only contain numbers'));

            return false;
        }

        if (strlen($value) !== $this->digits) {
            $this->addMessage(sprintf($this->translate('The token must be exactly %d digits long'), $this->digits));

            return false;
        }

        return true;
    }
}
