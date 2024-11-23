setup:
	@make clean
	@make build
	@make up


setup-update:
	@make build-update
	@make up


build:
	docker compose build --no-cache --force-rm
build-update:
	docker compose build --no-cache

stop:
	docker compose stop
up:
	docker compose up -d

fetch-articles:
	docker exec innoscripta_app bash -c "php artisan articles:fetch --source=newsapi"
	docker exec innoscripta_app bash -c "php artisan articles:fetch --source=guardian"
	docker exec innoscripta_app bash -c "php artisan articles:fetch --source=nytimes"


serve:
	docker exec innoscripta_app bash -c "php artisan serve"


clean:
	docker compose down --volumes --remove-orphans
	docker system prune -f
