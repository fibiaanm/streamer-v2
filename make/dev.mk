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
	@echo "    OpenSearch   → http://localhost:9200"
	@echo "    Dashboards   → http://localhost:5601"

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

buckets-flush: ## Vacía los buckets public y streamer en MinIO
	$(DC) run --rm \
		-e MINIO_ROOT_USER=$${MINIO_ROOT_USER:-minioadmin} \
		-e MINIO_ROOT_PASSWORD=$${MINIO_ROOT_PASSWORD:-minioadmin} \
		minio-setup /bin/sh -c "\
			/usr/bin/mc alias set local http://minio:9000 \$$MINIO_ROOT_USER \$$MINIO_ROOT_PASSWORD && \
			/usr/bin/mc rm --recursive --force local/public       2>/dev/null || true && \
			/usr/bin/mc rm --recursive --force local/\$${AWS_BUCKET:-streamer} 2>/dev/null || true && \
			echo 'MinIO: buckets vaciados.'"

migrate-fresh: buckets-flush ## Rollback completo + migra + seedea (también vacía buckets MinIO)
	$(DC) exec php php artisan migrate:fresh --seed

shell: ## Abre una shell en el contenedor php
	$(DC) exec php bash

# ─── Testing ──────────────────────────────────────────────────────────────────

test-db: ## Crea la BD streamer_test con extensiones (idempotente)
	@$(DC) exec postgres psql -U streamer -tc \
		"SELECT 1 FROM pg_database WHERE datname='streamer_test'" \
		| grep -q 1 \
		|| $(DC) exec postgres psql -U streamer \
		   -c "CREATE DATABASE streamer_test OWNER streamer"
	@$(DC) exec postgres psql -U streamer -d streamer_test \
		-c "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"; CREATE EXTENSION IF NOT EXISTS ltree;" \
		> /dev/null

test: test-db ## Ejecuta todos los tests con Pest
	$(DC) exec php ./vendor/bin/pest

test-unit: test-db ## Solo tests unitarios
	$(DC) exec php ./vendor/bin/pest tests/Unit

test-feature: test-db ## Solo tests de feature (excluye grupo pgsql)
	$(DC) exec php ./vendor/bin/pest tests/Feature --exclude-group=pgsql

test-pgsql: test-db ## Solo tests que requieren PostgreSQL (ltree, etc.)
	$(DC) exec php ./vendor/bin/pest --group=pgsql

.PHONY: prepare dev down restart build ps logs artisan migrate migrate-fresh buckets-flush shell \
        test-db test test-unit test-feature test-pgsql
