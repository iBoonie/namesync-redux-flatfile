<?php
require('./Lock/config.php');
require('./Lock/funcs.php');
require('./SleekDB/Store.php');

if (SECURE_TRIP_SALT === 'please-change-me')
{
    exit_error("Please change 'SECURE_TRIP_SALT' string in the config...", 409);
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'OPTIONS')
{
    header('Access-Control-Max-Age: 86400');
    exit;
}

$method  = filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST';
$origin  = filter_input(INPUT_SERVER, 'HTTP_ORIGIN') === 'https://boards.4chan.org';
$request = substr(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'), 0, 8) === 'NameSync';
$ip      = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

//check_valid($method,  'Invalid Request Method');
//check_valid($origin,  'Invalid Origin');
//check_valid($request, 'Invalid X-Requested-With');
//check_valid($ip,      'Invalid IP');

$board   = filter_input(INPUT_POST, 'b', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(' . ALL_BOARDS . ')$/')));
$thread  = filter_input(INPUT_POST, 't', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => PHP_INT_MAX)));
$post    = filter_input(INPUT_POST, 'p', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => PHP_INT_MAX)));
$name    = filter_input(INPUT_POST, 'n', FILTER_CALLBACK, array('options' => 'validate_strings'));
$subject = filter_input(INPUT_POST, 's', FILTER_CALLBACK, array('options' => 'validate_strings'));
$email   = filter_input(INPUT_POST, 'e', FILTER_CALLBACK, array('options' => 'validate_strings'));
$color   = filter_input(INPUT_POST, 'ca', FILTER_VALIDATE_INT, array('options' => array('default' => null, 'min_range' => 1, 'max_range' => 100)));
$hue     = filter_input(INPUT_POST, 'ch', FILTER_VALIDATE_INT, array('options' => array('default' => null, 'min_range' => 1, 'max_range' => 360)));

check_valid($board,  'Invalid Board');
check_valid($thread, 'Invalid Thread');
check_valid($post,   'Invalid Post');

if (ENABLE_RATE_LIMIT && is_flooding($board . '-sp', $ip, POST_LIMIT))
{
    exit_error("Flooding $board POST", 429);
}

if (is_null($name) && is_null($subject) && is_null($email))
{
    exit_error('Invalid Name + Subject + Email');
}

$boardsDir = DATA_DIR . $board;

// Make sure the data folder exists
if (!is_dir($boardsDir))
{
    mkdir($boardsDir);
}

// Dont allow client to post to far back (plus valid + alive thread)
if (VALIDATE_POST_RANGE && ($cache = get_board_data(DATA_DIR . 'board_data.json')) !== false)
{
    $oldThread = is_null($cache->$board->old) ? 0 : $cache->$board->old;
    $newPost = is_null($cache->$board->new) ? 0 : $cache->$board->new;

    if ($thread < $oldThread || $post < $newPost)
    {
        exit_error('Invalid post range');
    }
}

$Store = new \SleekDB\Store($thread, $boardsDir, [
    'primary_key' => 'p',
    'auto_cache'  => true,
    'timeout'     => false
]);

if ($Store->findById($post) !== null)
{
    exit_error('Post Already Exists', 409);
}

$generatePost = [
    'time'  => time(),
    'p'     => $post,
    'uid'   => hash_hmac('md5', $ip, SECURE_TRIP_SALT)
];

list($name, $trip) = trip($name);
push_if_not_null($generatePost, 'n', $name);
push_if_not_null($generatePost, 't', $trip);
push_if_not_null($generatePost, 's', $subject);
push_if_not_null($generatePost, 'e', $email);
push_if_not_null($generatePost, 'ca', $color);
push_if_not_null($generatePost, 'ch', $hue);

// false = use our own filename (p)
$Store->updateOrInsert($generatePost, false);