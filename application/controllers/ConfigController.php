<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Totp\Forms\DatabaseConfigForm;
use Icinga\Module\Totp\Forms\SettingsConfigForm;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tabs;
use ipl\Html\Contract\Form;
use ipl\Web\Compat\CompatController;

class ConfigController extends CompatController
{
    public function init(): void
    {
        $this->assertPermission('config/modules');

        parent::init();
    }

    public function databaseAction(): void
    {
        $form = (new DatabaseConfigForm(Config::module('totp')))
            ->on(Form::ON_SUBMIT, function (DatabaseConfigForm $_): void {
                Notification::success($this->translate('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('database'));

        $this->addContent($form);
    }

    public function settingsAction(): void
    {
        $form = (new SettingsConfigForm(Config::module('totp')))
            ->on(Form::ON_SUBMIT, function (SettingsConfigForm $_): void {
                Notification::success($this->translate('New configuration has successfully been stored'));
            })->handleRequest($this->getServerRequest());

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('settings'));

        $this->addContent($form);
    }

    protected function mergeTabs(Tabs $tabs): void
    {
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }
    }
}
