<?php
// Configuration for koharness - builds a standalone skeleton Kohana app for running unit tests
$base = [
    'modules' => [
        'unittest' => __DIR__.'/modules/unittest',
    ],
    'syspath' => __DIR__,
];

if (\getenv('KOHARNESS_MODULES') === 'all-modules') {
    $base['modules']['minion'] = __DIR__.'/modules/minion';
}
return $base;
