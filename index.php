<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting server...\n";
ob_flush();
flush();

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit;
}

if (socket_bind($socket, '0.0.0.0', 9090) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

if (socket_listen($socket, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

while (1) {
    $conn = socket_accept($socket);
    if ($conn === false) {
        echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
        continue;
    }
    $msg = "hello";
    socket_write($conn, $msg, strlen($msg));
    socket_close($conn);
    echo "Connection closed\n";
    ob_flush();
    flush();
}
?>
