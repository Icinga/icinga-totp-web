<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

/** @var $this \Icinga\Application\Modules\Module */

$this->provideConfigTab('database', [
    'title' => $this->translate('Database'),
    'label' => $this->translate('Database'),
    'url'   => 'config/database',
]);

$this->provideConfigTab('settings', [
    'title' => $this->translate('Configuration'),
    'label' => $this->translate('Configuration'),
    'url'   => 'config/settings',
]);
