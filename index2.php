<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting server...\n";

$socket = socket_create(AF_INET, SOCK_STREAM, 0);
var_dump($socket);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit;
}

if (socket_bind($socket, '0.0.0.0', 9090) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

if (socket_listen($socket) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

socket_set_nonblock($socket);
$clients = [];
$seconds = 0;

while (1) {
    if ($conn = socket_accept($socket)) {
        echo "conn is accepted\n";
        if ($conn !== false) {
        echo "conn is accepted\n";
            socket_set_nonblock($conn);
            $clients[] = $conn;
            $id = sizeof($clients) - 1;
            echo "conn #[$id] is connected\n";
        }
    }
    if (count($clients)) {
        foreach ($clients as $id => $conn) {
            if ($input = socket_read($conn, 512)) {
                $input = trim($input);
                echo "conn #[$id] input: $input\n";
                $ack = "OK\n";
                socket_write($conn, $ack, strlen($ack));
                if ($input == 'quit') {
                    socket_close($conn);
                    unset($clients[$id]);
                    echo "conn #[$id] is disconnected\n";
                }
            }
        }
    }
}

socket_close($socket);
?>
