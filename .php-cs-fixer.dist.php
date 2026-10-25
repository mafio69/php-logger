<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'dist',
        'test-workspace',
        'node_modules',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->notName('*.twig')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'single_line_empty_body' => false,
    ])
    ->setFinder($finder);
