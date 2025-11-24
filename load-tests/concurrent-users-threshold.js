import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import exec from 'k6/execution';

/*
 * Load test to validate that 200 concurrent users experience load times of ≤3 seconds
 * Tests all web.php routes simultaneously
 * 
 * Configure env vars before running:
 *   BASE_URL (required)       - e.g., https://eventflow.local
 *   SESSION_COOKIE (required) - auth cookie string (laravel_session=...; XSRF-TOKEN=...)
 *   APPROVER_HISTORY_EVENT_ID - sample ID for /approver/requests/history/{id}
 *   APPROVER_PENDING_EVENT_ID - sample ID for /approver/requests/pending/{id}
 *   ORG_EVENT_ID              - sample ID for /user/requests/{id}
 *   VENUE_ID                  - sample ID for /venues/{id}
 *   DOCUMENT_ID               - sample ID for /documents/{id}
 */

// Custom metrics for load time analysis
const loadTimeThreshold = new Trend('load_time_under_threshold');
const loadTimeFailureRate = new Rate('load_time_failures');

export const options = {
  scenarios: {
    concurrent_users: {
      executor: 'constant-vus',
      vus: 200,
      duration: '1.5m',
      gracefulStop: '30s',
    },
  },
  thresholds: {
    // All requests must complete within 3 seconds
    http_req_duration: ['p(100)<3000', 'p(99)<3000', 'p(95)<3000', 'p(90)<3000', 'p(80)<3000', 'p(70)<3000', 'avg<3000'],
    // Request failure rate must be minimal
    http_req_failed: ['rate<0.01'],
    // Custom metric: track load time failures
    load_time_failures: ['rate<0.01'],
  },
  userAgent: 'eventflow-concurrent-users-test/1.0',
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
if (!sessionCookie) throw new Error('SESSION_COOKIE is required to hit authenticated routes.');

const ids = {
  approverHistoryEventId: __ENV.APPROVER_HISTORY_EVENT_ID || defaults.APPROVER_HISTORY_EVENT_ID,
  approverPendingEventId: __ENV.APPROVER_PENDING_EVENT_ID || defaults.APPROVER_PENDING_EVENT_ID,
  orgEventId: __ENV.ORG_EVENT_ID || defaults.ORG_EVENT_ID,
  venueId: __ENV.VENUE_ID || defaults.VENUE_ID,
  documentId: __ENV.DOCUMENT_ID || defaults.DOCUMENT_ID,
};

const authHeaders = { Cookie: sessionCookie };

// All web routes from web.php
const endpoints = [
  { tag: 'public-calendar', path: '/', requiresAuth: false },
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
];

export default function () {
  const vu = exec.vu.idInInstance;
  const iter = exec.scenario.iterationInInstance;

  endpoints.forEach((endpoint) => {
    const res = hitEndpoint(endpoint, vu, iter);
    
    const loadTimeInMs = res.timings.duration;
    const isWithinThreshold = loadTimeInMs <= 3000;

    // Track custom metrics
    loadTimeThreshold.add(loadTimeInMs, { endpoint: endpoint.tag });
    loadTimeFailureRate.add(!isWithinThreshold, { endpoint: endpoint.tag });

    // Check response status and load time
    check(res, {
      [`${endpoint.tag}: status 200`]: (r) => r.status === 200,
      [`${endpoint.tag}: load time <= 3s`]: (r) => r.timings.duration <= 3000,
    }, { endpoint: endpoint.tag });

    sleep(0.1 + Math.random() * 0.2);
  });
}

function hitEndpoint(endpoint, vu, iter) {
  const method = endpoint.method || 'GET';
  const path = typeof endpoint.path === 'function' ? endpoint.path() : endpoint.path;
  const url = `${BASE_URL}${path}`;
  
  const headers = Object.assign(
    { 'User-Agent': 'eventflow-concurrent-users-test/1.0' },
    endpoint.requiresAuth === false ? {} : authHeaders,
    endpoint.headers || {},
  );

  return http.request(method, url, null, {
    headers,
    tags: { endpoint: endpoint.tag },
  });
}
