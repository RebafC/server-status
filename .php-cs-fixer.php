<?php

declare(strict_types=1);

$header = <<<'EOF'
    This file is part of PHP CS Fixer.

    (c) Fabien Potencier <fabien@symfony.com>
        Dariusz Rumiński <dariusz.ruminski@gmail.com>

    This source file is subject to the MIT license that is bundled
    with this source code in the file LICENSE.
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->exclude(['dev-tools/phpstan', 'tests/Fixtures'])
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setIndent('    ')
    ->setRules([
        '@PSR2' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        '@PHPUnit100Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        // one should use PHPUnit built-in method instead
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']],
        // 'header_comment' => ['header' => $header],
        'modernize_strpos' => true, // needs PHP 8+ or polyfill
        // TODO switch back on when the `src/Console/Application.php` no longer needs the concat
        'no_useless_concat_operator' => false,
        'concat_space' => ['spacing' => 'one'],
        'static_lambda' => true,
    ])
    ->setFinder($finder)
;

return $config;
