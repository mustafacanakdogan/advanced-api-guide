import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 2,
  duration: '15s',
  thresholds: {
    checks: ['rate>0.99'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.K6_EMAIL || 'demo@example.com';
const PASSWORD = __ENV.K6_PASSWORD || 'password';

export default function () {
  const url = `${BASE_URL}/api/v1/auth/token`;
  const payload = JSON.stringify({ email: EMAIL, password: PASSWORD });
  const params = {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  };

  const res = http.post(url, payload, params);

  check(res, {
    'status is 200 or 401 or 429': (r) => [200, 401, 429].includes(r.status),
  });

  sleep(0.2);
}
