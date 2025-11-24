import http from 'k6/http';
import { check, sleep } from 'k6';
import exec from 'k6/execution';

/*
 * Load test that covers all public and authenticated endpoints.
 * Configure env vars before running:
 *   BASE_URL (required)            - e.g., https://eventflow.local
 *   SESSION_COOKIE (required)      - auth cookie string (laravel_session=...; XSRF-TOKEN=...)
 *   APPROVER_HISTORY_EVENT_ID      - sample ID for /approver/requests/history/{id}
 *   APPROVER_PENDING_EVENT_ID      - sample ID for /approver/requests/pending/{id}
 *   ORG_EVENT_ID                   - sample ID for /user/requests/{id}
 *   VENUE_ID                       - sample ID for /venues/{id}
 *   DOCUMENT_ID                    - sample ID for /documents/{id}
 *   NEXO_API_KEY (required)        - API key for /api/nexo-import
 *   NEXO_SOURCE_ID (optional)      - defaults to "nexo"
 */

export const options = {
  scenarios: {
    ramp_and_hold: {
      executor: 'ramping-vus',
      startVUs: 20,
      stages: [
        { duration: '1m', target: 200 },
        { duration: '3m', target: 200 },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '30s',
    },
  },
  thresholds: {
    http_req_duration: ['avg<2000', 'p(95)<3000'],
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
  },
  userAgent: 'eventflow-load-test/1.0',
};

const BASE_URL = (__ENV.BASE_URL || '').replace(/\/+$/, '');
if (!BASE_URL) throw new Error('Set BASE_URL (e.g., https://eventflow.test)');

const sessionCookie = __ENV.SESSION_COOKIE;
if (!sessionCookie) throw new Error('SESSION_COOKIE is required to hit authenticated routes.');

const ids = {
  approverHistoryEventId: __ENV.APPROVER_HISTORY_EVENT_ID,
  approverPendingEventId: __ENV.APPROVER_PENDING_EVENT_ID,
  orgEventId: __ENV.ORG_EVENT_ID,
  venueId: __ENV.VENUE_ID,
  documentId: __ENV.DOCUMENT_ID,
};
Object.entries(ids).forEach(([key, value]) => {
  if (!value) throw new Error(`Set ${key} to resolve dynamic Livewire routes.`);
});

const nexoApiKey = __ENV.NEXO_API_KEY;
if (!nexoApiKey) throw new Error('Set NEXO_API_KEY to call /api/nexo-import.');
const nexoSourceId = __ENV.NEXO_SOURCE_ID || 'nexo';

const authHeaders = { Cookie: sessionCookie };

const endpoints = [
  { tag: 'public-calendar', path: '/', requiresAuth: false },
  { tag: 'mail-preview', path: '/mail/test', requiresAuth: false },
  { tag: 'admin-users', path: '/admin/users' },
  { tag: 'admin-departments', path: '/admin/departments' },
  { tag: 'admin-venues', path: '/admin/venues' },
  { tag: 'dsca-categories', path: '/dsca/categories' },
  { tag: 'admin-events', path: '/admin/events' },
  { tag: 'admin-audit-log', path: '/admin/audit-log' },
  { tag: 'admin-audit-download', path: '/admin/audit-log/download' },
  { tag: 'approver-history-index', path: '/approver/requests/history' },
  { tag: 'approver-history-details', path: () => `/approver/requests/history/${ids.approverHistoryEventId}` },
  { tag: 'approver-pending-index', path: '/approver/requests/pending' },
  { tag: 'approver-pending-details', path: () => `/approver/requests/pending/${ids.approverPendingEventId}` },
  { tag: 'org-requests-index', path: '/user/requests' },
  { tag: 'org-requests-details', path: () => `/user/requests/${ids.orgEventId}` },
  { tag: 'venues-index', path: '/venues' },
  { tag: 'venues-show', path: () => `/venues/${ids.venueId}` },
  { tag: 'venues-requirements', path: () => `/venues/requirements/${ids.venueId}` },
  { tag: 'event-create', path: '/event/create' },
  { tag: 'events-create-alias', path: '/events/create' },
  { tag: 'director-venues', path: '/director' },
  { tag: 'documents-show', path: () => `/documents/${ids.documentId}` },
  { tag: 'documents-pdf', path: () => `/documents/${ids.documentId}/pdf` },
  {
    tag: 'nexo-import',
    method: 'POST',
    path: '/api/nexo-import',
    requiresAuth: false,
    requiresApiKey: true,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    payload: (vu, iter) => JSON.stringify({
      source_id: nexoSourceId,
      payload: {
        name: `Load Test Org ${vu}-${iter}`,
        email: `loadtest+${vu}-${iter}@example.com`,
        assoc_id: 10000 + vu * 100 + iter,
        association_name: 'Load Test Association',
        counselor: 'Load Tester',
        email_counselor: 'loadtester@example.com',
      },
    }),
    expectedStatus: [200],
  },
];

export default function () {
  const vu = exec.vu.idInInstance;
  const iter = exec.scenario.iterationInInstance;

  endpoints.forEach((endpoint) => {
    const res = hitEndpoint(endpoint, vu, iter);
    check(res, {
      [`${endpoint.tag}: status`]: (r) => (endpoint.expectedStatus || [200]).includes(r.status),
      [`${endpoint.tag}: <3s`]: (r) => r.timings.duration < 3000,
    }, { endpoint: endpoint.tag });

    sleep(0.3 + Math.random() * 0.7);
  });
}

function hitEndpoint(endpoint, vu, iter) {
  const method = endpoint.method || 'GET';
  const path = typeof endpoint.path === 'function' ? endpoint.path() : endpoint.path;
  const url = `${BASE_URL}${path}`;
  const params = buildParams(endpoint);
  const bodyBuilder = endpoint.payload;
  const body = (method === 'GET' || method === 'DELETE')
    ? null
    : (typeof bodyBuilder === 'function' ? bodyBuilder(vu, iter) : bodyBuilder);

  return http.request(method, url, body, params);
}

function buildParams(endpoint) {
  const headers = Object.assign(
    { 'User-Agent': 'eventflow-load-test/1.0' },
    endpoint.requiresAuth === false ? {} : authHeaders,
    endpoint.requiresApiKey ? { 'X-API-KEY': nexoApiKey } : {},
    endpoint.headers || {},
  );

  return {
    headers,
    tags: {
      endpoint: endpoint.tag,
      group: endpoint.tag.includes('nexo') ? 'api' : 'web',
    },
  };
}
