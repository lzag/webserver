<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!extension_loaded('pcntl')) { 
    echo "PCNTL extension required.\n"; 
    exit; 
}

echo "Starting server...\n";

// https://beej.us/guide/bgnet/html/split/system-calls-or-bust.html#socket
$socket = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit;
}

// https://beej.us/guide/bgnet/html/split/system-calls-or-bust.html#bind
if (socket_bind($socket, '0.0.0.0', 9090) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

// https://beej.us/guide/bgnet/html/split/system-calls-or-bust.html#listen
if (socket_listen($socket) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

echo "Sever listening  on port 9090\n";

// https://beej.us/guide/bgnet/html/split/slightly-advanced-techniques.html#blocking
socket_set_nonblock($socket);
$clients = [];
$seconds = 0;
$running = true;

// Signal handler for SIGINT
pcntl_signal(SIGINT, function () use (&$running) {
    $running = false;
    echo "\nCaught signal, shutting down...\n";
});

while ($running) {
    // https://beej.us/guide/bgnet/html/split/system-calls-or-bust.html#acceptthank-you-for-calling-port-3490%2E
    if ($conn = socket_accept($socket)) {
        if ($conn !== false) {
        echo "New connection is accepted\n";
            socket_set_nonblock($conn);
            $clients[] = $conn;
            $id = sizeof($clients) - 1;
            echo "Connection #[$id] is connected\n";
        }
    }
    if (count($clients)) {
        foreach ($clients as $id => $conn) {
            // https://beej.us/guide/bgnet/html/split/system-calls-or-bust.html#sendrecv
            if ($input = socket_read($conn, 512)) {
                $input = trim($input);
                echo "Connection #[$id] input: $input\n";
                $ack = "HTTP/1.0 200 OK\r\nContent-Length: 0\r\n\r\n";
                socket_write($conn, $ack, strlen($ack));
                if ($input == 'quit') {
                    socket_close($conn);
                    unset($clients[$id]);
                    echo "conn #[$id] is disconnected\n";
                }
            }
        }
    }
    // Dispatch any pending signals
    pcntl_signal_dispatch();
    usleep(1);
}

echo "Closing all active connections...\n";
foreach ($clients as $id => $conn) {
    socket_close($conn);
    echo "Closed client connection #[$id]\n";
}
socket_close($socket);
echo "Server socket closed.\n";
