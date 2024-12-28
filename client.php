<?php
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, '127.0.0.1', 9090);
echo socket_read($socket, 2);
socket_close($socket);
