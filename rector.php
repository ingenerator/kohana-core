<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withSets([
        PHPUnitSetList::COMPOSER_BASED,
    ])
    ->withRootFiles()
    ->withImportNames(
        removeUnusedImports: true,
    );
