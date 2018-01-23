SHELL := /usr/bin/env bash

start:
	docker-compose build
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

down:
	docker-compose down

top:
	docker-compose top