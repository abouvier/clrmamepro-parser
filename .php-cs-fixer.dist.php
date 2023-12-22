<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2023 Alexandre Bouvier <contact@amb.tf>
//
// SPDX-License-Identifier: CC0-1.0

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in(__DIR__);

$config = new Config();

return $config->setFinder($finder)->setRules([
    '@Symfony' => true,
]);
