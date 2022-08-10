<?php
function is_flooding($id, $ip, $flooding = 5, $floodTime = 60)
{
    $tmp = sys_get_temp_dir() . '/NSredux/';
    $md5 = md5($ip);
    $UID = $tmp . "$md5-$id";

    if (!is_dir($tmp))
    {
        mkdir($tmp);
    }

    if (!is_file($UID))
    {
        file_put_contents($UID, 0, LOCK_EX);
        return false;
    }

    $time = filemtime($UID);

    if ($time === false || (time() - $time) >= $floodTime)
    {
        file_put_contents($UID, 0, LOCK_EX);
        return false;
    }

    if (($hits = file_get_contents($UID)) !== false)
    {
        // If we get garbled data, hits = 0
        $hits = intval($hits);

        // So we only call syslog once
        if ($hits < 0)
        {
            return true;
        }

        if ($hits >= $flooding - 1)
        {
            file_put_contents($UID, -1, LOCK_EX);
            touch($UID, $time); // Set original creation time

            // Block from your firewall if you wish
            syslog(LOG_INFO, "NSRFlood: $ip");

            return true;
        }
    
        $hits += 1;
        file_put_contents($UID, $hits, LOCK_EX);
        touch($UID, $time);
    
        return false;
    }

    file_put_contents($UID, 0, LOCK_EX);
    return false;
}

function get_board_data($file)
{
    if (!file_exists($file))
    {
        return false;
    }

    $file = file_get_contents($file);
    if ($file === false)
    {
        return false;
    }

    $json = json_decode($file);

    if ($json !== true)
    {
        return false;
    }

    return $json;
}

function validate_strings($str)
{
    $str = htmlspecialchars($str);

    if (strlen($str) == 0)
    {
        return null;
    }

    return $str;
}

function trip($name)
{
    // Return name if non-valid trip
    if (!preg_match('/^([^#]+)?(##|#)(.+)$/', $name, $match))
    {
        return array($name, null);
    }

    $name = $match[1];
    $secure = $match[2];
    $trip = $match[3];

    if (strcmp($secure, '##') == 0)
    {
        // This will never be a 1:1 with 4chan, so whatever
        $salt = md5($name . SECURE_TRIP_SALT . $trip);
        $trip = '!!' . substr(crypt($trip, $salt), -10);
    } else {
        // UTF-8 > SJIS
        $trip = mb_convert_encoding($trip, 'Shift_JIS', 'UTF-8');
        $salt = substr($trip . 'H..', 1, 2);
        $salt = preg_replace('/[^.-z]/', '.', $salt);
        $salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');
        $trip = '!' . substr(crypt($trip, $salt), -10);
    }

    if (strlen($name) === 0)
    {
        $name = null;
    }

    return array($name, $trip);
}

function push_if_not_null(&$array, $id, $value)
{
    if (!is_null($value))
    {
        $array = array_merge($array, [$id => $value]);
    }
} 

function check_valid($check, $output)
{
    if (!$check || is_null($check))
    {
        exit_error($output);
    }
}

function exit_error($response, $type = 406)
{
    http_response_code($type);
    exit($response);
}

function scan_dir($dir)
{
    return array_diff(scandir($dir), array('.', '..'));
}
