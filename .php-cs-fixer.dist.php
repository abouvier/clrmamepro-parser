<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in(__DIR__);

$config = new Config();

return $config->setFinder($finder)->setIndent("\t")->setRules([
	'@Symfony' => true,
]);
