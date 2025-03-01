from locust import HttpUser, task, between

class SocketServerUser(HttpUser):
    host = "http://php-server:9090"
    wait_time = between(1, 3)  # Wait 1-3 seconds between requests

    @task
    def get_root(self):
        self.client.get("/")
