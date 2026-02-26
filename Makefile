.PHONY: load-test load-test-auth load-test-idem load-test-slow

load-test: load-test-auth load-test-idem load-test-slow

load-test-auth:
	docker compose --profile k6 run --rm k6 run /scripts/auth-rate-limit.js

load-test-idem:
	docker compose --profile k6 run --rm k6 run /scripts/idempotency.js

load-test-slow:
	docker compose --profile k6 run --rm k6 run /scripts/slow-latency.js