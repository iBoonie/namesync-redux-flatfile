<?php
require('config.php');

$data = array();
$stickies = array();

$b = explode('|', ALL_BOARDS);

// Get newest Post
foreach ($b as $key => $board)
{
    $url = (object) curl_url("https://a.4cdn.org/$board/1.json");

    if (isset($url->error))
    {
        error_log("Failed parsing /$board/ api. Response: $url->response, Curl: $url->curl, Json: $url->json");

        // Default to 0
        $data = array_merge($data, [
            $board => ['new' => 0]
        ]);

        continue;
    }

    foreach ($url->threads as $key => $thread)
    {
        $posts = $thread->posts;

        if (isset($posts[0]->sticky))
        {
            // Push stickies into an array
            array_push($stickies, $board . $posts[0]->no);

            continue;
        }

        // Last element is the newest post on the board
        $postno = end($posts)->no;

        $data = array_merge($data, [
            $board => ['new' => $postno]
        ]);

        break;
    }

    // Lets be nice to the API
    sleep(1);
}

// Get oldest Thread
foreach ($b as $key => $board)
{
    $url = (object) curl_url("https://a.4cdn.org/$board/threads.json");
    $oldest = 0;

    if (isset($url->error))
    {
        error_log("Failed parsing /$board/ api. Response: $url->response, Curl: $url->curl, Json: $url->json");

        // Default to 0
        $data[$board] = array_merge($data[$board], 
            ['old' => 0]
        );

        continue;
    }

    // Seperated by pages
    foreach ($url as $page)
    {
        $thread = $page->threads;

        foreach ($thread as $post)
        {
            $no = $post->no;

            // Skip if sticky
            if (in_array($board . $no, $stickies))
            {
                continue;
            }

            if (!$oldest || $no < $oldest)
            {
                $oldest = $no;
            }
        }
    }

    $data[$board] = array_merge($data[$board], 
        ['old' => $oldest]
    );

    sleep(1);
}

if (!is_dir(DATA_DIR))
{
    mkdir(DATA_DIR);
}

file_put_contents(DATA_DIR . 'board_data.json', json_encode($data));
echo json_encode($data);

function curl_url($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
    curl_setopt($ch, CURLOPT_ENCODING,  '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $json = json_decode(curl_exec($ch), false);
    $response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curl_errno = curl_errno($ch);
    curl_close($ch);

    if($response_code !== 200 || empty($json) || $curl_errno > 0)
    {
        return ['error' => true, 'response' => $response_code, 'curl' => $curl_errno, 'json' => $json];
    }

    return $json;
}