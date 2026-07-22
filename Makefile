.DEFAULT_GOAL := help

help: ## Lista los comandos disponibles
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

install: ## Instala las dependencias de desarrollo (Pest, Testbench)
	composer install --no-interaction --prefer-dist

test: ## Corre la suite Pest (paquete puro, sin BD)
	vendor/bin/pest

pint: ## Formatea el código
	vendor/bin/pint

pint-test: ## Verifica el formato sin modificar
	vendor/bin/pint --test

ci: ## Corre lo mismo que el CI (formato + tests). Úsalo antes de abrir una PR.
	@echo "-- 1/2 . Formato (Pint) --"
	@$(MAKE) --no-print-directory pint-test
	@echo "-- 2/2 . Tests (Pest) --"
	@$(MAKE) --no-print-directory test
	@echo ""
	@echo "CI local en verde."
