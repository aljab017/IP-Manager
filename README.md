# IP Manager

_IP Manager application implemented in Drupal 9_

This module provides IP Range and IP Change entities with business logic and UI for creating, managing, and registering IP address changes with external 3rd party systems.

## Platform requirements

To enable EzProxy git exports, your server (or Docker image) needs `git` and `ssh-client` installed.
Below is a Dockerfile snippet that works with any of the official Drupal 9 Docker images:

```Dockerfile
FROM drupal:9-php7.4-apache-buster
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get -y --no-install-recommends install git ssh-client && \
    apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
```
