<?php
use parallel\Runtime;

// Create a thread pool with a specified number of threads
$poolSize = 10;
$pool = [];
for ($i = 0; $i < $poolSize; $i++) {
    $pool[] = new Runtime();
}

// Function to process a request
$processRequest = function ($input): string {
    // simulate a slow request
    sleep(1);
    $docRoot = "/var/www/html";
    @list($method, $path, $protocol) = explode(' ', $input, 3);
    if (!isset($method) || !isset($path) || !isset($protocol)) {
        return "HTTP/1.1 400 Bad Request\r\n\r\n";
    }
    if ($path == '/') {
        $path = $docRoot . "/" . "page.html";
    } else {
        $path = $docRoot . $path;
    }
    if (!file_exists($path)) {
        $response = "HTTP/1.1 404 Not Found\r\nRequested path: $path\r\n\r\n";
    } else {
        $responseBody = file_get_contents($path);
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/html\r\n";
        $response .= "Content-Length: " . strlen($responseBody);
        $response .= "\r\n\r\n";
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
$running = true;

// Signal handler for SIGINT
pcntl_signal(SIGINT, function () use (&$running) {
    $running = false;
    echo "\nCaught signal, shutting down...\n";
});

while ($running || !empty($futures)) {
    $readfds = array_merge($clients, [$socket]);
    $writefds = NULL;
    $errorfds = [];
    if ($running) {
        // https://beej.us/guide/bgnet/html/split/slightly-advanced-techniques.html#select
        $select = socket_select($readfds, $writefds, $errorfds, 0, 1000);
        if ($select > 0) {
            // we're checking if any connections are ready to be read and adding them to the connections
            if (in_array($socket, $readfds, true) === true) {
                $incoming_conn = socket_accept($socket);
                socket_set_nonblock($incoming_conn);
                if (count($clients) >= 10) {
                    $refusal = "HTTP/1.1 503 Service Unavailable\r\nContent-Length: 20\r\n\r\nServer is full, sorry";
                    socket_write($incoming_conn, $refusal, strlen($refusal));
                    socket_close($incoming_conn);
                    echo "Refused a connection - client limit (10) reached\n";
                } else {
                    echo "Connection is accepted\n";
                    // adding the socket to the clients array
                    $clients[] = $incoming_conn;
                }
                // Remove socket from futher procesing in the loop
                $key = array_search($socket, $readfds, true);
                if ($key !== false) {
                    unset($readfds[$key]);
                }
            }

            // reading from the connections
            foreach ($readfds as $conn) {
                // Submit the request to the thread pool
                $connkey = array_search($conn, $clients, true);
                if ($input = socket_read($conn, 8192)) {
                    $input = trim($input);
                    // submitting the request to the thread pool
                    $futures[$connkey] = $pool[array_rand($pool)]->run($processRequest, [$input]);
                } else {
                    unset($clients[$connkey]);
                    echo "The connection is closed\n";
                }
            }
        }
    }

    foreach ($futures as $key => $future) {
        if ($future->done()) {
            $response = $future->value();
            $conn = $clients[$key];
            if (!$conn) {
                echo "Connection #[$key] is closed\n";
                print_r($clients);
                unset($futures[$key]);
                continue;
            }
            echo "Conn #[$key] writing response...\n";
            socket_write($conn, $response, strlen($response));
            unset($futures[$key]);
            socket_close($conn);
            unset($clients[$key]);
        }
    }
    // dispatch signals
    pcntl_signal_dispatch();
}
echo "Closing down server\n";

socket_close($socket);
