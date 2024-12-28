<?php
use parallel\{Runtime, Future, Channel};

// Create a thread pool with a specified number of threads
$poolSize = 10;
$pool = [];
for ($i = 0; $i < $poolSize; $i++) {
    $pool[] = new Runtime();
}
$indexFile = 'page.html';
// Function to process a request
$processRequest = function ($input): string {
    // sleep(1);
    @list($method, $path, $protocol) = explode(' ', $input, 3);
    if (!isset($method) || !isset($path) || !isset($protocol)) {
        return "HTTP/1.1 400 Bad Request\r\n\r\n";
    }
    if ($path == '/') {
        $path = "/page.html";
    }
    if (!file_exists(__DIR__ . $path)) {
        $response = "HTTP/1.1 404 Not Found\r\nRequested path: $path\r\n\r\n";
    } else {
        $responseBody = file_get_contents(__DIR__ . $path);
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/html\r\n";
        $response .= "Content-Length: " . strlen($responseBody) . "\r\n";
        $response .= $responseBody;
        $response .= "\r\n\r\n";
    }

    return $response;
};

$socket = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($socket, '0.0.0.0', 9090);
socket_listen($socket);
socket_set_nonblock($socket);

$clients = [];
// create a thread pool
$futures = [];

// reading from sockets
// Add socket accept monitoring

while (1) {
    // implement signal handling
    $readfds = array_merge($clients, [$socket]);
    $writefds = NULL;
    $errorfds = [];
    // https://beej.us/guide/bgnet/html/split/slightly-advanced-techniques.html#select
    // var_dump($readfds);
    $select = socket_select($readfds, $writefds, $errorfds, 0, 1000);
    if ($select > 0) {
        // we're checking if any connections are ready to be read and adding them to the connections
        if (in_array($socket, $readfds, true) === true) {
            // var_dump($socket);
            echo "socket";
            var_dump($socket);
            $conn = socket_accept($socket);
            socket_set_nonblock($conn);
            echo "conn is accepted\n";
            var_dump($conn);
            // $id = rand(1, 1000);
            $clients[] = $conn;
            // echo "conn #[$id] is connected\n";
            // Remove socket accept monitoring
            $key = array_search($socket, $readfds, true);
            if ($key !== false) {
                // var_dump($readfds);
                echo "key: $key\n";
                unset($readfds[$key]);
            }
        }
        // var_dump($readfds);

        // reading from the connections
        foreach ($readfds as $_conn) {
            echo "reading from connnssssss ---------------------". PHP_EOL;
            var_dump($_conn);

            // Submit the request to the thread pool
            $_connkey = array_search($_conn, $clients, true);

            if ($input = @socket_read($_conn, 8192)) {
                $input = trim($input);
                // Simulate processing time
                echo "conn #[$id] input: $input\n";
                // submitting the request to the thread pool
                $futures[$_connkey] = $pool[array_rand($pool)]->run($processRequest, [$input]);
            } else {
                unset($clients[$_connkey]);
                echo "The connection is closed\n";
            }
        }
    } else {
        echo "No activity on sockets\n";
    }

    // looping through the futures
    foreach ($futures as $key => $future) {
        if ($future->done()) {
            $response = $future->value();
            $_conn = $clients[$key];
            if (!$_conn) {
                echo "Connection is closed\n";
                continue;
            }
            echo "conn #[$key] response: $response\n";
            socket_write($_conn, $response, strlen($response));
            unset($futures[$key]);
            socket_close($_conn);
            unset($clients[$key]);
        } else {
            echo "Not done\n";
        }
        // echo(count($futures)) . PHP_EOL;
    }
}
socket_close($socket);
