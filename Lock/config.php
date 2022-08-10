<?php
// Directory to store all board data
// Requires trailing slash
define('DATA_DIR', __DIR__ . '/../Data/');

// Boards seperated by |. Example: b|soc|trash
define('ALL_BOARDS', 'b|trash');

// Salt used for encrypting secure trips
define('SECURE_TRIP_SALT', 'please-change-me');

// Dont allow people to add data to old posts
// This requires you to have set up a cronjon for 'CRON_POST_LIMITS.php'
// Set 0 to Disable
define('VALIDATE_POST_RANGE', 1);

// Time in seconds to cache the catalog
// Set 0 to Disable
define('CATALOG_CACHE_TIME', 60);

// Time in days to keep thread data
// This requires you to have set up a cronjon for 'CRON_CLEANUP.php'
// Set 0 to Disable
define('THREAD_CACHE_TIME', 30);

// Post/Get are based on a 60 second timer, limits are also in seconds
// Rate limiting is board independant
// If rate limiting is disabled, config options under this wont matter
// If a client hits a limit, they will get logged to syslog (for firewall blocking)
// Example syslog hit: NSRFlood: 192.168.1.1
// Set 0 to Disable
define('ENABLE_RATE_LIMIT', 1);

// Posts
define('POST_LIMIT', 5);

// Querying index/threads for new posts
define('GET_LIMIT', 150);

// Delete posts
define('DELETE_LIMIT', array(
    'delete' => 1,       // This many deletes
    'every'  => 86400,   // Every this many seconds (default 86400 = 24 hours)
));