<?php
// Configuration for koharness - builds a standalone skeleton Kohana app for running unit tests
$base = [
    'modules' => [
        'unittest' => __DIR__.'/vendor/kohana/unittest',
    ],
    'syspath' => __DIR__,
];

if (\getenv('KOHARNESS_ALL_MODULES')) {
    $base['modules']['minion'] = __DIR__.'/modules/minion';
}
return $base;
