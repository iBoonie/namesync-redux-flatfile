<?php
require('./Lock/config.php');
require('./Lock/funcs.php');
require('./SleekDB/Store.php');

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'OPTIONS')
{
    header('Access-Control-Max-Age: 86400');
    exit;
}

$origin  = filter_input(INPUT_SERVER, 'HTTP_ORIGIN') === 'https://boards.4chan.org';
$request = substr(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'), 0, 8) === 'NameSync';
$ip      = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

//check_valid($origin,  'Invalid Origin');
//check_valid($request, 'Invalid X-Requested-With');
//check_valid($ip,      'Invalid IP');

// Limit delete to once a day
if (ENABLE_RATE_LIMIT && is_flooding('rm', $ip, DELETE_LIMIT['delete'], DELETE_LIMIT['every']))
{
    exit_error(DELETE_LIMIT['delete'] . ' delete every ' . DELETE_LIMIT['every'] . '/s', 429);
}

$baseDir = DATA_DIR;
if (!is_dir($baseDir))
{
    exit;
}

$boards = scan_dir($baseDir);
if (!$boards)
{
    exit;
}

foreach ($boards as $key => $board)
{
    $board = "$baseDir/$board";
    if (!is_dir($board)) 
    {
        continue;
    }

    $threads = scan_dir($board);
    if (!$threads)
    {
        continue;
    }

    foreach ($threads as $key => $thread)
    {
        if (!is_dir("$board/$thread"))
        {
            continue;
        }

        $Store = new \SleekDB\Store($thread, $board, [
            'primary_key' => 'uid',
            'timeout'     => false
        ]);

        $result = $Store->search(["uid"], hash_hmac('md5', $ip, SECURE_TRIP_SALT));

        foreach ($result as $value)
        {
            if (array_key_exists('p', $value))
            {
                $Store->deleteById($value['p']);
            }
        }
    }
}