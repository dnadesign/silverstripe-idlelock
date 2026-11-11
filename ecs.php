<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withConfiguredRule(
        ReferenceUsedNamesOnlySniff::class,
        [
            'allowFallbackGlobalFunctions' => false,
            'allowFallbackGlobalConstants' => false,
        ]
    )
    ->withPreparedSets(
        psr12: true,
        common: true,
    )
    ->withSkip([
        NotOperatorWithSuccessorSpaceFixer::class,
        // See: https://github.com/silverstripe/silverstripe-standards/issues/8
        SelfAccessorFixer::class,
    ]);
