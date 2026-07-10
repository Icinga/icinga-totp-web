<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Forms;

use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\ConfigForm;

class DatabaseConfigForm extends ConfigForm
{
    protected function assemble(): void
    {
        $dbResources = ResourceFactory::getResourceConfigs('db')->keys();

        $this->addElement('select', 'database__resource', [
            'label'        => $this->translate('Database'),
            'options'      => array_combine($dbResources, $dbResources),
            'pleaseChoose' => true,
            'required'     => true,
        ]);
    }
}
