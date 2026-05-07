DC = docker compose

# ─── Events & Reminders ───────────────────────────────────────────────────────

reminder-fire: ## Dispara un reminder run por job ID: make reminder-fire id=42
	$(DC) exec php php artisan reminder:fire $(id)
