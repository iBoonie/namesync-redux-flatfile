<?php
require('config.php');

if (THREAD_CACHE_TIME === 0)
{
    return;
}

$baseDir = DATA_DIR;
$boards = scan_dir($baseDir);

if (!$boards)
{
    return;
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
        $thread = "$board/$thread";
        if (!is_dir($thread))
        {
            continue;
        }

        if ((time() - filemtime($thread)) >= (THREAD_CACHE_TIME * 60 * 60 * 24))
        {
            delete_dir($thread);
        }
    }
}

function scan_dir($dir)
{
    return array_diff(scandir($dir), array('.', '..'));
}

function delete_dir($dir)
{
    $files = scan_dir($dir);
    if (!$threads)
    {
        foreach($files as $file)
        {
            if(is_dir("$dir/$file"))
            {
                delete_dir("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
    }

    return rmdir($dir);
} 