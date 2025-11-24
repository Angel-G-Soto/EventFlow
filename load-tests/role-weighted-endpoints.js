import http from 'k6/http';
import { check, sleep } from 'k6';
import exec from 'k6/execution';

import { fail } from 'k6';
import { Trend } from 'k6/metrics';
import { Counter } from 'k6/metrics';
import { Rate } from 'k6/metrics';


/**
 * Separate role scenarios:
 * - calendar: 200 VUs (public calendar / landing)
 * - requestor: 100 VUs (create/My Requests/documents)
 * - approver: 90 VUs (pending/history/venues/documents)
 * - admin: 10 VUs (users/departments/venues/events/audit/nexo)
 *
 * NOTE: This config will use up to 400 concurrent VUs total.
 * Scale the 'vus' numbers if you need to stay under a global limit.
 */
export const options = {
  thresholds: {
    http_req_duration: ['avg<2000', 'p(95)<3000'],
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
  },
  userAgent: 'eventflow-load-test/roles/1.0',
  scenarios: {
    calendar: {
      executor: 'constant-vus',
      vus: 1,
      duration: '3m',
      gracefulStop: '30s',
      exec: 'calendarScenario',
    },
    requestor: {
      executor: 'constant-vus',
      vus: 100,
      duration: '3m',
      gracefulStop: '30s',
      exec: 'requestorScenario',
    },
    approver: {
      executor: 'constant-vus',
      vus: 90,
      duration: '3m',
      gracefulStop: '30s',
      exec: 'approverScenario',
    },
    admin: {
      executor: 'constant-vus',
      vus: 10,
      duration: '3m',
      gracefulStop: '30s',
      exec: 'adminScenario',
    },
  },
};

const defaults = {
  BASE_URL: 'https://eventflow.test',
  SESSION_COOKIE: 'laravel_session=eyJpdiI6ImE2enFQYnMwZ2tiZGdjaFBrZEl4M0E9PSIsInZhbHVlIjoiRXZrRzQ1emFYaXFib00ybFFaWnNkYWp2ZVQ0b0FDN2tXV0ViczQzQnRNdkN6WWJ1cmg5OXR0ZG52ZnFzUEdkTGt1cnRnTVN3bUhkV1B2NVpJSlpKOFJWaStSOWl5WVNGYTRtVTh5N0o5NmplaU9xUGZTL0swTlNUaVdnaS84dE0iLCJtYWMiOiI5OTU0NDk2MmQyZWNjYTZlODIyNzNmNWNlZTgxYTY0NzMwYmIyYzc0YzFmYTIwNmFkYTdjMTgxYmE0YjI3N2YzIiwidGFnIjoiIn0%3D; XSRF-TOKEN=eyJpdiI6ImFEYnYza1BFdGxvaHExK2FlL3EybEE9PSIsInZhbHVlIjoiSHZkUjdscGlLRitXaUVyd0ZqM3RJQXFDbU9CaDkwR3RQMlpoWHlUa3VhL2hhSFAxWldlM3lTZzV0YmtZWk1CZmZDcnJMNDZXbWZsWUVoWE1Jb29Ja0dUdmNoVkl4TmNYY29UMmMyZVdjQldXaXRzeUxpakllTTFHUkprYkdXUHgiLCJtYWMiOiJlODhkYzRjNjFkNjQ4NjViMzM4NmMyN2VjYzI4NDM1MTBmMjhkZjU1NzQxMDMyOWI3YTI4ZmZhOTg5MWI0OThjIiwidGFnIjoiIn0%3D',
  APPROVER_HISTORY_EVENT_ID: '23',
  APPROVER_PENDING_EVENT_ID: '23',
  ORG_EVENT_ID: '23',
  VENUE_ID: '2',
  DOCUMENT_ID: '28',
  NEXO_API_KEY: 'evf_live_Gd4bJHnCksU1cOzUba2vZVd0P3m7WQi-8F2y9tLrAcY',
  NEXO_SOURCE_ID: 'nexo',
};

const BASE_URL = (__ENV.BASE_URL || defaults.BASE_URL).replace(/\/+$/, '');
if (!BASE_URL) throw new Error('Set BASE_URL (e.g., https://eventflow.test)');

const sessionCookie = __ENV.SESSION_COOKIE || defaults.SESSION_COOKIE;
const nexoApiKey = __ENV.NEXO_API_KEY || defaults.NEXO_API_KEY;

const ids = {
  approverHistoryEventId: defaults.APPROVER_HISTORY_EVENT_ID,
  approverPendingEventId: defaults.APPROVER_PENDING_EVENT_ID,
  orgEventId: defaults.ORG_EVENT_ID,
  venueId: defaults.VENUE_ID,
  documentId: defaults.DOCUMENT_ID,
};

const authHeaders = sessionCookie ? { Cookie: sessionCookie } : {};
const apiHeaders = nexoApiKey ? { 'X-API-KEY': nexoApiKey } : {};

// ----- Endpoint groups -----

const calendarEndpoints = [
  { tag: 'public-calendar', path: '/', requiresAuth: false },
];

const requestorEndpoints = [
  { tag: 'event-create', path: '/event/create' },
  { tag: 'events-create-alias', path: '/events/create' },
  { tag: 'org-requests-index', path: '/user/requests' },
  { tag: 'org-requests-details', path: `/user/requests/${ids.orgEventId}` },
  { tag: 'documents-show', path: `/documents/${ids.documentId}` },
];

const approverEndpoints = [
  { tag: 'approver-pending-index', path: '/approver/requests/pending' },
  { tag: 'approver-pending-details', path: `/approver/requests/pending/${ids.approverPendingEventId}` },
  { tag: 'approver-history-index', path: '/approver/requests/history' },
  { tag: 'approver-history-details', path: `/approver/requests/history/${ids.approverHistoryEventId}` },
  { tag: 'venues-index', path: '/venues' },
  { tag: 'venues-show', path: `/venues/${ids.venueId}` },
  { tag: 'venues-requirements', path: `/venues/requirements/${ids.venueId}` },
  { tag: 'documents-pdf', path: `/documents/${ids.documentId}/pdf` },
];

const adminEndpoints = [
  { tag: 'admin-users', path: '/admin/users' },
  { tag: 'admin-departments', path: '/admin/departments' },
  { tag: 'admin-venues', path: '/admin/venues' },
  { tag: 'dsca-categories', path: '/dsca/categories' },
  { tag: 'admin-events', path: '/admin/events' },
  { tag: 'admin-audit-log', path: '/admin/audit-log' },
  { tag: 'admin-audit-download', path: '/admin/audit-log/download' },
  {
    tag: 'nexo-import',
    method: 'POST',
    path: '/api/nexo-import',
    requiresAuth: false,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    requiresApiKey: true,
    payload: (vu, iter) =>
      JSON.stringify({
        source_id: __ENV.NEXO_SOURCE_ID || defaults.NEXO_SOURCE_ID,
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

// ----- Helpers -----

function pick(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function buildParams(endpoint) {
  const headers = Object.assign(
    { 'User-Agent': 'eventflow-load-test/roles/1.0' },
    endpoint.requiresAuth === false ? {} : authHeaders,
    endpoint.requiresApiKey ? apiHeaders : {},
    endpoint.headers || {},
  );

  return { headers, tags: { endpoint: endpoint.tag } };
}

function hitEndpoint(endpoint) {
  const method = endpoint.method || 'GET';
  const path = typeof endpoint.path === 'function' ? endpoint.path() : endpoint.path;
  const url = `${BASE_URL}${path}`;
  const params = buildParams(endpoint);

  const bodyBuilder = endpoint.payload;
  const body =
    method === 'GET' || method === 'DELETE'
      ? null
      : typeof bodyBuilder === 'function'
        ? bodyBuilder(exec.vu.idInInstance, exec.scenario.iterationInInstance)
        : bodyBuilder;

  const res = http.request(method, url, body, params);
  return { res, tag: endpoint.tag, expectedStatus: endpoint.expectedStatus || [200] };
}

function runOnceFromGroup(endpoints) {
  const ep = pick(endpoints);
  const { res, tag, expectedStatus } = hitEndpoint(ep);

  const ok = check(res, {
    [`${tag}: status`]: (r) => expectedStatus.includes(r.status),
    [`${tag}: <3s`]: (r) => r.timings.duration < 3000,
  });

  if (!ok) {
    // Be careful with PDFs / large bodies – truncate
    const bodyPreview = (res.body && typeof res.body === 'string')
      ? res.body.slice(0, 200)
      : '<non-text body>';

    console.error(
      `Unexpected status for ${tag}: ${res.status} ` +
      `method=${res.request.method} url=${res.request.url} bodyPreview=${bodyPreview}`
    );
  }

  sleep(0.3 + Math.random() * 0.7);
}


// ----- Scenarios (entry points) -----

export function calendarScenario() {
  runOnceFromGroup(calendarEndpoints);
}

export function requestorScenario() {
  runOnceFromGroup(requestorEndpoints);
}

export function approverScenario() {
  runOnceFromGroup(approverEndpoints);
}

export function adminScenario() {
  runOnceFromGroup(adminEndpoints);
}

// default is unused when using named scenarios, but k6 requires it to be present in some setups
export default function () {
  // no-op; all work is done in the named scenario functions above
}
