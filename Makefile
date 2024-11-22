setup:
	@make clean
	@make build
	@make up
	@make composer-install


setup-update:
	@make build-update
	@make up
	@make composer-update


build:
	docker compose build --no-cache --force-rm
build-update:
	docker compose build --no-cache

stop:
	docker compose stop
up:
	docker compose up -d

composer-install:
	docker exec innoscripta_app bash -c "composer install --no-dev"

composer-update:
	docker exec innoscripta_app bash -c "composer update --no-dev"


serve:
	docker exec innoscripta_app bash -c "php artisan serve"


clean:
	docker compose down --volumes --remove-orphans
	docker system prune -f
