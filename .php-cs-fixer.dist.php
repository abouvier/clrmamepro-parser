<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2023 Alexandre Bouvier <contact@amb.tf>
//
// SPDX-License-Identifier: Apache-2.0

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in(__DIR__);

$config = new Config();

return $config->setFinder($finder)->setIndent("\t")->setRules([
	'@Symfony' => true,
]);
