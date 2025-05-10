# HTML Web Server

## Overview

This repository contains two PHP-based TCP socket servers running on port 9090, showcasing different approaches to handling client connections.

### Server 1: Non-Blocking Single-Threaded Server
- **File**: `single.php`
- **Description**: A lightweight, non-blocking server using PHP’s `socket` functions. It listens for connections, reads client input, and echoes it back with an HTTP 200 OK response. Connections close on "quit" input.  
- **Key Features**:  
  - Non-blocking I/O with `socket_set_nonblock()`.  
  - Single-threaded, polling clients in a `while` loop.  
  - Handles SIGINT for graceful shutdown.  
- **Use Case**: Basic concurrency demo without threading.  

### Server 2: Multi-Threaded Server with Thread Pool
- **File**: `multi.php` 
- **Description**: An enhanced server using `parallel\Runtime` for multi-threading. It processes HTTP-like requests (e.g., GET) in a pool of 10 threads, serving files from `/var/www/html` or returning 404s.  
- **Key Features**:  
  - Thread pool with 10 `Runtime` instances for parallel request handling.  
  - `socket_select()` for efficient socket monitoring.  
  - Caps at 10 clients, rejecting others with HTTP 503.  
  - Queues tasks if threads are busy, balancing load.  
- **Use Case**: Demonstrates threading and scalability for heavier workloads.

Running the single-threaded server
```bash
INDEX_FILE=single.php docker compose up 
```
Running the multi-threaded server
```bash
INDEX_FILE=multi.php docker compose up
```
Test the server with a simple PHP client
```bash
docker compose exec php-server php client.php
```
Test single-threaded connection with netcat (if it's single-threaded server you can type quit to close the connection)
```bash
docker compose exec php-server sh -c 'echo "GET / HTTP/1.0\r\n\r\n" | netcat localhost 9090'
```
Running docker compoes up will also start Locust that can be accessed in browser on localhost://8089
