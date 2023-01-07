<?php

$config = new PhpCsFixer\Config();

$config->setRules([
    '@PSR12' => true,
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ],
    'php_unit_test_annotation' => [
        'style' => 'annotation',
    ],
    'php_unit_test_class_requires_covers' => false,
    'php_unit_internal_class' => false,
    'no_unused_imports' => true,
]);

$config->setRiskyAllowed(true);

$config->setCacheFile(__DIR__.'/.cache/.php-cs-fixer.cache');

$config->setFinder(
    PhpCsFixer\Finder::create()->in(__DIR__)
);

return $config;
