import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 1,
  duration: '10s',
  thresholds: {
    http_req_duration: ['p(95)<2500'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.K6_EMAIL || 'demo@example.com';
const PASSWORD = __ENV.K6_PASSWORD || 'password';

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

  const url = `${BASE_URL}/api/v1/slow?sleep_ms=1200`;
  const params = {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  };

  const res = http.get(url, params);

  check(res, {
    'status is 200': (r) => r.status === 200,
  });

  sleep(0.5);
}
