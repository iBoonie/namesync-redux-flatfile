<?php
require('./Lock/config.php');
require('./Lock/funcs.php');
require('./SleekDB/Store.php');

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');
header('Content-Type: application/json; charset=utf-8');

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'OPTIONS')
{
    header('Access-Control-Max-Age: 86400');
    exit;
}

$method  = filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET';
$origin  = filter_input(INPUT_SERVER, 'HTTP_ORIGIN') === 'https://boards.4chan.org';
$request = substr(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'), 0, 8) === 'NameSync';
$ip      = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

//check_valid($method,  'Invalid Request Method');
//check_valid($origin,  'Invalid Origin');
//check_valid($request, 'Invalid X-Requested-With');
//check_valid($ip,      'Invalid IP');

$board  = filter_input(INPUT_GET, 'b', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(' . ALL_BOARDS . ')$/')));
$thread = filter_input(INPUT_GET, 't', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[\d,]*$/')));

check_valid($board,  'Invalid Board');
check_valid($thread, 'Invalid Thread');

$boardsDir = DATA_DIR . $board;

if (!is_dir($boardsDir))
{
    exit;
}

if (ENABLE_RATE_LIMIT && is_flooding($board . '-qp', $ip, GET_LIMIT))
{
    exit_error("Flooding $board GET", 429);
}

// 165 threads * 9 (thread int length) + 164 commas = 1649 + 165 (for when thread numbers roll over to 10 len) + leeway = 1900
if (strlen($thread) > 1900)
{
    exit_error('Thread String to Large');
}

// Catalog thread numbers are comma seperated
if (preg_match('/,/', $thread))
{
    $catalogCache = "$boardsDir/catalog.json";

    // Load the cache instead
    if (CATALOG_CACHE_TIME !== 0 && is_file($catalogCache) && (time() - filemtime($catalogCache)) < CATALOG_CACHE_TIME)
    {
        exit(file_get_contents($catalogCache));
    }

    $array = array();
    $threads = explode(',', $thread);

    // Check if any of catalog posts have posts associated with them
    foreach ($threads as $threadFolder)
    {
        if (empty($threadFolder))
        {
            continue;
        }

        $threadDir = "$boardsDir/$threadFolder";

        if (!is_dir($threadDir))
        {
            continue;
        }

        // Push the thread folder number into the array
        array_push($array, [
            'p' => $threadFolder
        ]);

        // Scan the thread folder and push each post number into the array 
        // This is so we know how many total posts the board has
        $postsDir = "$threadDir/data/";

        $scan = scan_dir($postsDir);
        if (!$scan)
        {
            continue;
        }

        foreach ($scan as $postFile)
        {
            if (is_dir($postsDir . $postFile))
            {
                continue;
            }

            array_push($array, [
                'p' => pathinfo($postFile, PATHINFO_FILENAME)
            ]);
        }
    }

    file_put_contents($catalogCache, json_encode($array));
    exit(json_encode($array));
}
else
{
    if (is_dir("$boardsDir/$thread"))
    {
        $Store = new \SleekDB\Store($thread, $boardsDir, [
            'primary_key' => 'p',
            'auto_cache'  => true,
            'timeout'     => false
        ]);

        $result = $Store->findAll();

        exit(json_encode($result));
    }
}