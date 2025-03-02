from locust import HttpUser, task, between

class SocketServerUser(HttpUser):
    host = "http://php-server:9090"

    @task
    def get_root(self):
        self.client.get("/")
