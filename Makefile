.PHONY: up down build restart migrate seed sim test logs shell ps

# Default target
all: up

# Build and start all Docker services
up:
	docker compose up -d --build

# Stop all Docker services
down:
	docker compose down

# Rebuild containers
build:
	docker compose build

# Restart containers
restart:
	docker compose restart

# Run database migrations
migrate:
	docker compose exec app php artisan migrate

# Seed database with initial users, site, and demo device
seed:
	docker compose exec app php artisan db:seed

# Run hardware device simulator (for demo device ST-DEMO-001)
sim:
	docker compose exec app php artisan tracker:simulate ST-DEMO-001

# Run test suite inside the container
test:
	docker compose exec app php artisan test

# Stream all container logs
logs:
	docker compose logs -f

# Stream specific service logs (e.g. make log-mqtt, make log-app)
log-app:
	docker compose logs -f app

log-mqtt:
	docker compose logs -f mqtt-listener mosquitto

# Open interactive bash shell in the app container
shell:
	docker compose exec app bash

# View container status
ps:
	docker compose ps
