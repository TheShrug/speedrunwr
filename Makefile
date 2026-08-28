# Primary CLI surface for speedrunwr. Each target is a thin wrapper over docker
# compose or artisan; see CLAUDE.md.
#
# The fleet interface is `make build test run` plus `make database` across every
# app repo — TheShrug/homelab Conventions/Local Dev Interface.md.

# Assigned in the fleet port table, not defaulted, so two apps can run at once.
PORT         ?= 8002
# DB_HOST_PORT, not DB_PORT: compose substitutes from .env, where Laravel's
# DB_PORT=5432 is the app's INTERNAL connection port. See docker-compose.yml.
DB_HOST_PORT ?= 55402
export PORT DB_HOST_PORT

COMPOSE      := docker compose
TEST_COMPOSE := docker compose --profile test

DB_ACTIONS := download restore migrate

.DEFAULT_GOAL := help
.PHONY: help build test run database $(DB_ACTIONS)

help: ## Show this help
	@echo "speedrunwr — make targets:"
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) \
	  | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-10s %s\n", $$1, $$2}'
	@echo
	@echo "  stop the stack with: docker compose down"

.env:
	@echo "==> no .env, creating one from .env.example"
	@cp .env.example .env
	@sed -i 's|^DB_HOST=.*|DB_HOST=db|; s|^DB_PASSWORD=.*|DB_PASSWORD=secret|' .env
	@# DB_HOST is emptied deliberately: docker/entrypoint-dev.sh blocks on
	@# pg_isready whenever DB_HOST is set, so --no-deps without this hangs
	@# forever waiting for a database that was never started.
	@$(COMPOSE) run --rm -T --no-deps -e DB_HOST= app php artisan key:generate

build: ## Build the dev image
	$(COMPOSE) build

# NOT `docker compose run app php artisan test`. The `app` service carries
# env_file: .env, which puts DB_* into the container's real OS environment;
# Laravel's env() reads $_SERVER and phpunit.xml's <env> does not win, so
# RefreshDatabase truncates whatever `app` points at. That is #14. The `test`
# service has an explicit environment block and its own tmpfs Postgres.
#
# --fail-on-warning is not optional: CI's first green run reported success with
# five Feature tests that never executed.
test: ## Run the suite against a throwaway Postgres — never the dev database
	$(TEST_COMPOSE) run --rm -T test php artisan test --fail-on-warning

run: .env ## Serve the app on 8002 (override PORT=); prints the URL last
	@$(COMPOSE) up -d
	@printf '==> waiting for http://localhost:$(PORT)/ '
	@for _ in $$(seq 1 60); do \
	   if curl -sfo /dev/null "http://localhost:$(PORT)/"; then break; fi; \
	   printf '.'; sleep 2; \
	 done; echo
	@$(COMPOSE) exec -T app php artisan migrate --force >/dev/null 2>&1 || true
	@echo
	@echo "  speedrunwr serving on http://localhost:$(PORT)/  (postgres on $(DB_HOST_PORT))"

database: ## Run one or more DB actions in order, e.g. `make database download restore migrate`
	@actions="$(filter $(DB_ACTIONS),$(MAKECMDGOALS))"; \
	if [ -z "$$actions" ]; then \
		echo "usage: make database <action> [<action> ...]  (actions: $(DB_ACTIONS))" >&2; \
		exit 1; \
	fi; \
	for action in $$actions; do \
		case "$$action" in \
			download) ./Build/backup-database.sh ;; \
			restore)  ./Build/restore-database.sh ;; \
			migrate)  ./Build/migrate-database.sh ;; \
		esac; \
	done

# Swallow the action words so `make database download` doesn't also try to build
# a literal target named "download".
$(DB_ACTIONS):
	@:
