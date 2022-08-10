# Namesync Redux Flatfile
- MIT implementation of Namesync's server
- Secure tripcodes work
- Tripcode passwords are **NOT** stored
- Old post data can be automatically deleted
- Configurable rate limiting

## Installation
- Add the following Cronjobs
- `0 * * * * php /path/to/namesync/Lock/CRON_CLEANUP.php`
- `* * * * * php /path/to/namesync/Lock/CRON_POST_LIMITS.php`
- Configuration is located in `/Lock/config.php`
- **If you are using Apache, you are good to go! If not, please deny access to Data, Lock and SleekDB folders.**

## Other
Using a flat DB structure is a lot faster than using something like mysql/mariadb like the original NamesyncRedux did (https://github.com/iBoonie/namesyncredux). Being able to call a cache file resulted in a crazy speed improvement.