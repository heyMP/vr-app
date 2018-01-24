SHELL := /usr/bin/env bash

start:
	docker-compose build
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

sync_into_container:
	docker cp ./contenta/web vrapp_php_1:/var/www/web

down:
	docker-compose down

top:
	docker-compose top