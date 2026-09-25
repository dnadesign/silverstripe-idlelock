<?php

declare(strict_types=1);

use Cambis\SilverstripeRector\Set\ValueObject\SilverstripeSetList;
use Netwerkstatt\SilverstripeRector\Set\SilverstripeLevelSetList;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withImportNames(removeUnusedImports: true)
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withComposerBased(phpunit: true)
    ->withPhpSets()
    ->withSets([
        SilverstripeLevelSetList::UP_TO_SS_6_2,
        SetList::CODE_QUALITY,
        SilverstripeSetList::CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withSkip([
        ClosureToArrowFunctionRector::class,
        // These may cause a downgrade to fail
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddTypeToConstRector::class,
    ]);
