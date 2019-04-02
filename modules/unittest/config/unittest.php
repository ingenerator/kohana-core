<?php \defined('SYSPATH') or die('No direct script access.');

return [
    // If you don't use a whitelist then only files included during the request will be counted
    // If you do, then only whitelisted items will be counted
    'use_whitelist' => TRUE,

    // Items to whitelist, only used in cli
    'whitelist'     => [],

    // Does what it says on the tin
    // Blacklisted files won't be included in code coverage reports
    // If you use a whitelist then the blacklist will be ignored
    'use_blacklist' => FALSE,

    // List of individual files/folders to blacklist
    'blacklist'     => [],
];
