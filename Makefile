PROJECT = matura
NETWORK = traefik
DATABASE = bdd
WEB = php
NODEJS = nodejs
NODEJS_EXEC = sh
COMPOSE = docker-compose -p $(PROJECT)

start: network up config install
destroy: stop rm

.PHONY: up
up:
	@$(COMPOSE) up -d

.PHONY: ps
ps:
	@$(COMPOSE) ps

.PHONY: stop
stop:
	@$(COMPOSE) stop

.PHONY: rm
rm:
	@$(COMPOSE) rm -f

.PHONY: logs
logs:
	$(eval app ?= $(WEB))
	@$(COMPOSE) logs -f $(app)

.PHONY: build
build:
	@$(COMPOSE) build

.PHONY: fesh-build
fresh-build:
	@$(COMPOSE) build --no-cache

.PHONY: network
network:
	@docker network create $(NETWORK) 2> /dev/null || true

.PHONY: exec
exec:
	$(eval app ?= $(WEB))
	$(eval user ?= www-data)
	$(eval cmd ?= bash)
	@$(COMPOSE) exec --user=$(user) $(app) $(cmd)

.PHONY: exec-nodejs
exec-nodejs:
	@$(COMPOSE) exec $(NODEJS) $(NODEJS_EXEC)

.PHONY: mysql
mysql:
	$(eval db_name = $(shell $(COMPOSE) exec $(DATABASE) bash -c 'echo $$MYSQL_DATABASE'))
	@$(COMPOSE) exec $(DATABASE) mariadb -u root -proot $(db_name)

.PHONY: install
install:
	$(eval user ?= www-data)
	@$(COMPOSE) exec --user=$(user) $(WEB) composer install
	@$(COMPOSE) exec --user=$(user) $(WEB) bin/console d:d:c --if-not-exists
	@$(COMPOSE) exec --user=$(user) $(WEB) bin/console d:s:u --force
#	@$(COMPOSE) exec --user=$(user) $(WEB) bin/console d:m:m
	@$(COMPOSE) exec --user=$(user) $(WEB) bin/console lexik:jwt:generate-keypair --overwrite
	@$(COMPOSE) exec --user=$(user) $(WEB) php bin/console --env=test doctrine:database:create --if-not-exists
	@$(COMPOSE) exec --user=$(user) $(WEB) php bin/console --env=test doctrine:schema:create

.PHONY: package
package:
	$(eval user ?= www-data)
	@$(COMPOSE) exec --user=$(user) $(WEB) ./bin/package

.PHONY: phpstan
phpstan:
	$(eval user ?= www-data)
	@$(COMPOSE) exec --user=$(user) $(WEB) vendor/bin/phpstan analyse src tests --memory-limit=1G

.PHONY: phpcs
phpcs:
	$(eval user ?= www-data)
	@$(COMPOSE) exec --user=$(user) $(WEB) vendor/bin/php-cs-fixer fix


.PHONY: phpunit
phpunit:
	$(eval user ?= www-data)
	@$(COMPOSE) exec --user=$(user) -e APP_ENV=test $(WEB) php bin/phpunit

.PHONY: test
test: phpstan phpunit
