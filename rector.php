<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
     ->withPhpSets()
    ->withAttributesSets()
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
