<?php
$socket = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($socket, '0.0.0.0', 9090);
socket_listen($socket);
socket_set_nonblock($socket);

$clients = [];
$indexFile = 'page.html';

while (1) {
    // Add socket accept monitoring
    $readfds = array_merge($clients, array($socket));
    $writefds = NULL;
    $errorfds = $clients;
    // https://beej.us/guide/bgnet/html/split/slightly-advanced-techniques.html#select
    $select = socket_select($readfds, $writefds, $errorfds, 3);

    if ($select > 0) {
        // we're checking if any connections are ready to be read and adding them to the connections
        if (in_array($socket, $readfds)) {
            $conn = socket_accept($socket);
            $clients[] = $conn;
            $id = sizeof($clients) - 1;
            echo "conn #[$id] is connected\n";
            // Remove socket accept monitoring
            $key = array_search($socket, $readfds);
            unset($readfds[$key]);
        }

        // reading from the connections
        foreach ($readfds as $_conn) {
            if ($input = @socket_read($_conn, 512)) {
                $input = trim($input);
                echo "conn #[$id] input: $input\n";
                list($method, $path, $protocol) = explode(' ', $input, 3);
                if (!isset($method) || !isset($path) || !isset($protocol)) {
                    $response = "HTTP/1.1 400 Bad Request\r\n\r\n";
                    socket_write($_conn, $response, strlen($response));
                    continue;
                }
                if ($path == '/') {
                    $path = "/$indexFile";
                }
                if (!file_exists(__DIR__ . $path)) {
                    $response = "HTTP/1.1 404 Not Found\r\nRequested path: $path\r\n\r\n";
                } else {
                    $response = "HTTP/1.1 200 OK\r\nRequested path: $path\r\n\r\n";
                    $response .= file_get_contents(__DIR__ . $path);
                }
                socket_write($_conn, $response, strlen($response));
            }
        }
    } else {
        echo "No activity\n";
    }
}
?>
