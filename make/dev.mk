DC = docker compose

# ─── Primera vez ──────────────────────────────────────────────────────────────

prepare: ## Primera vez: copia .env, construye imágenes y levanta servicios
	@if [ ! -f backend/.env ]; then \
		cp backend/.env.example backend/.env; \
		echo "  backend/.env creado desde backend/.env.example — revisa las variables antes de continuar"; \
	fi
	$(DC) build
	$(DC) up -d
	@echo ""
	@echo "  Servicios disponibles:"
	@echo "    App          → http://localhost:8000"
	@echo "    Socket.io    → http://localhost:3000"
	@echo "    Mailpit      → http://localhost:8025"
	@echo "    MinIO        → http://localhost:9001"
	@echo "    pgAdmin      → http://localhost:5050"
	@echo "    RedisInsight → http://localhost:5540"
	@echo "    Meilisearch  → http://localhost:7700"

# ─── Ciclo de vida ────────────────────────────────────────────────────────────

dev: ## Levanta todos los contenedores en background
	$(DC) up -d

down: ## Detiene y elimina los contenedores (preserva volúmenes)
	$(DC) down

restart: ## Reinicia todos los contenedores
	$(DC) restart

build: ## Reconstruye las imágenes (usar tras cambiar un Dockerfile)
	$(DC) build

ps: ## Estado de los contenedores
	$(DC) ps

logs: ## Sigue los logs de todos los servicios
	$(DC) logs -f

logs-%: ## Sigue los logs de un servicio: make logs-php
	$(DC) logs -f $*

# ─── Laravel ──────────────────────────────────────────────────────────────────

artisan: ## Ejecuta un comando artisan: make artisan cmd="route:list"
	$(DC) exec php php artisan $(cmd)

migrate: ## Ejecuta las migraciones
	$(DC) exec php php artisan migrate

migrate-fresh: ## Rollback completo + migra + seedea
	$(DC) exec php php artisan migrate:fresh --seed

shell: ## Abre una shell en el contenedor php
	$(DC) exec php bash

# ─── Testing ──────────────────────────────────────────────────────────────────

test: ## Ejecuta todos los tests con Pest
	$(DC) exec php ./vendor/bin/pest

test-unit: ## Solo tests unitarios
	$(DC) exec php ./vendor/bin/pest tests/Unit

test-feature: ## Solo tests de feature (SQLite)
	$(DC) exec php ./vendor/bin/pest tests/Feature --exclude-group=pgsql

test-pgsql: ## Solo tests que requieren PostgreSQL
	$(DC) exec php ./vendor/bin/pest --group=pgsql

.PHONY: prepare dev down restart build ps logs artisan migrate migrate-fresh shell \
        test test-unit test-feature test-pgsql
