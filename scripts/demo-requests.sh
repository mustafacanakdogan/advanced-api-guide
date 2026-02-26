#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
EMAIL="${DEMO_EMAIL:-demo@example.com}"
PASSWORD="${DEMO_PASSWORD:-password}"

create_user_local() {
  php artisan tinker --execute="\\App\\Models\\User::updateOrCreate(['email'=>'${EMAIL}'], ['name'=>'Demo User','password'=>bcrypt('${PASSWORD}')]);"
}

create_user_docker() {
  docker compose exec -T app php artisan tinker --execute="\\App\\Models\\User::updateOrCreate(['email'=>'${EMAIL}'], ['name'=>'Demo User','password'=>bcrypt('${PASSWORD}')]);"
}

if command -v docker >/dev/null 2>&1 && docker compose ps -q app >/dev/null 2>&1; then
  create_user_docker
else
  create_user_local
fi

TOKEN="$(curl -s -X POST "${BASE_URL}/api/v1/auth/token" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" \
  | php -r '$d=json_decode(stream_get_contents(STDIN), true); echo $d["data"]["token"] ?? "";')"

if [[ -z "${TOKEN}" ]]; then
  echo "Token alınamadı. API yanıtını kontrol et."
  exit 1
fi

IDEMPOTENCY_KEY="demo-$(date +%s)"

for _ in {1..5}; do
  curl -s "${BASE_URL}/api/v1/users" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}" >/dev/null
 done

# force a 4xx error
curl -s -X POST "${BASE_URL}/api/v1/payments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Idempotency-Key: error-${IDEMPOTENCY_KEY}" \
  -d '{"amount": 0, "currency": "us"}' >/dev/null

# slow request (server-side delay)
curl -s "${BASE_URL}/api/v1/slow?sleep_ms=1500" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" >/dev/null

# hit metrics endpoint
curl -s "${BASE_URL}/api/metrics" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" >/dev/null

echo "Demo istekleri gönderildi. Grafana dashboard’u kontrol edebilirsin."
