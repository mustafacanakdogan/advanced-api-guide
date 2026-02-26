import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 1,
  duration: '10s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.K6_EMAIL || 'demo@example.com';
const PASSWORD = __ENV.K6_PASSWORD || 'password';
const IDEM_KEY = __ENV.IDEMPOTENCY_KEY || 'k6demo_idem_1234567890';

function getToken() {
  const url = `${BASE_URL}/api/v1/auth/token`;
  const payload = JSON.stringify({ email: EMAIL, password: PASSWORD });
  const params = {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  };

  const res = http.post(url, payload, params);
  if (res.status !== 200) return null;
  const json = res.json();
  return (json && json.data && json.data.token) ? json.data.token : null;
}

export default function () {
  const token = getToken();
  if (!token) return;

  const url = `${BASE_URL}/api/v1/payments`;
  const payload = JSON.stringify({ amount: 120.5, currency: 'usd' });
  const params = {
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'Idempotency-Key': IDEM_KEY,
    },
  };

  const first = http.post(url, payload, params);
  const second = http.post(url, payload, params);

  check(first, {
    'first status is 201': (r) => r.status === 201,
  });

  check(second, {
    'second status is 201 or 409': (r) => [201, 409].includes(r.status),
    'second has idempotent header when cached': (r) =>
      r.status !== 201 || r.headers['X-Idempotent'] === 'true',
  });

  sleep(0.5);
}
