<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in(__DIR__);

$config = new Config();

return $config->setFinder($finder)->setRules([
    '@Symfony' => true,
]);
