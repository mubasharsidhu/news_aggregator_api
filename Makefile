setup:
	@make build
	@make up
	@make composer-install
	@make add-deps


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
	docker exec me_rmt_innoscripta_news_aggregator_api bash -c "composer install"

composer-update:
	docker exec me_rmt_innoscripta_news_aggregator_api bash -c "composer update"

data:
	docker exec me_rmt_innoscripta_news_aggregator_api bash -c "php artisan migrate"
	docker exec me_rmt_innoscripta_news_aggregator_api bash -c "php artisan db:seed"

serve:
	docker exec me_rmt_innoscripta_news_aggregator_api bash -c "php artisan serve"


clean:
	docker compose down --volumes --remove-orphans
	docker system prune -f


add-deps:
	composer require --dev wecodemore/laravel-security-sniffer
