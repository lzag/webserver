<?php
# just a simple client to test the server
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, '127.0.0.1', 9090);
socket_write($socket, "GET / HTTP/1.1\r\n\r\n"); // Send a basic HTTP request
echo socket_read($socket, 1024); // Read more bytes for full response
socket_close($socket);
