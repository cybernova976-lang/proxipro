const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {join} = require('node:path');
const {runInNewContext} = require('node:vm');
const source = readFileSync(join(__dirname, '../../public/js/user-presence.js'), 'utf8');

function harness({hidden = false, online = true, response = {status: 200, state: 'online'}} = {}) {
  let now = 100000;
  const documentEvents = {}, windowEvents = {}, intervals = [], requests = [], beacons = [];
  const label = {textContent: 'Hors ligne'};
  const indicator = {dataset: {presenceConversation: '12', state: 'offline', validFor: '75'}, querySelector: () => label};
  const document = {
    hidden,
    querySelector: selector => selector.startsWith('script') ? {dataset: {heartbeatUrl: '/presence/heartbeat', interval: '25'}} : {content: 'test-csrf'},
    querySelectorAll: () => [indicator],
    addEventListener: (name, fn) => { documentEvents[name] = fn; },
  };
  const navigator = {onLine: online, sendBeacon: (url, body) => { beacons.push({url, body}); return true; }};
  const window = {addEventListener: (name, fn) => { windowEvents[name] = fn; }};
  let reply = response;
  runInNewContext(source, {
    document, navigator, window, crypto: {randomUUID: () => 'b40f13bc-f1d8-4819-9900-e2c198b6d9b3'},
    WeakMap, Set, Array, Number, Math, FormData, AbortController, Error,
    Date: class extends Date { static now() { return now; } },
    setTimeout: () => 1, clearTimeout: () => {}, setInterval: (fn, ms) => { intervals.push({fn, ms}); },
    fetch: async (url, options) => {
      requests.push({url, ...options, data: JSON.parse(options.body)});
      if (reply instanceof Error) throw reply;
      return {status: reply.status, ok: reply.status === 200, json: async () => ({presences: {'12': {state: reply.state, valid_for_seconds: 75, checked_at: now}}})};
    },
  });
  return {document, navigator, indicator, label, requests, beacons, intervals,
    settle: () => new Promise(resolve => setImmediate(resolve)),
    response: value => { reply = value; }, advance: ms => { now += ms; },
    visibility: value => { document.hidden = value; documentEvents.visibilitychange(); },
    event: (name, arg) => windowEvents[name](arg),
  };
}

test('visible authenticated page reports activity and paints confirmed presence', async () => {
  const h = harness(); await h.settle();
  assert.equal(h.requests.length, 1);
  assert.equal(h.requests[0].data.active, true);
  assert.equal(h.requests[0].headers['X-CSRF-TOKEN'], 'test-csrf');
  assert.deepEqual([...h.requests[0].data.conversation_ids], [12]);
  assert.equal(h.indicator.dataset.state, 'online');
  assert.equal(h.label.textContent, 'En ligne');
  assert.ok(h.intervals.some(timer => timer.ms === 25000));
});

test('hidden page releases only its tab then sends a newer signal when visible', async () => {
  const h = harness(); await h.settle(); h.visibility(true);
  assert.equal(h.beacons.length, 1);
  assert.equal(h.beacons[0].body.get('active'), '0');
  assert.equal(h.beacons[0].body.get('sequence'), '2');
  assert.equal(h.beacons[0].body.get('_token'), 'test-csrf');
  h.intervals.find(timer => timer.ms === 25000).fn(); await h.settle();
  assert.equal(h.requests.length, 1);
  h.visibility(false); await h.settle();
  assert.equal(h.requests[1].data.sequence, 3);
});

test('background tab or disconnected browser cannot keep user online', async () => {
  const hidden = harness({hidden: true}); await hidden.settle(); assert.equal(hidden.requests.length, 0);
  const offline = harness({online: false}); await offline.settle(); assert.equal(offline.requests.length, 0);
});

test('stale presence and connection failure are unknown instead of falsely online or offline', async () => {
  const h = harness(); await h.settle();
  h.advance(76000); h.intervals.find(timer => timer.ms === 1000).fn();
  assert.equal(h.label.textContent, 'Présence indisponible');
  h.response(new Error('Network failure')); h.intervals.find(timer => timer.ms === 25000).fn(); await h.settle();
  assert.equal(h.indicator.dataset.state, 'unknown');
  h.response({status: 200, state: 'offline'}); h.event('online'); await h.settle();
  assert.equal(h.label.textContent, 'Hors ligne');
});

test('logout or expired authentication stops further heartbeats', async () => {
  const h = harness({response: {status: 401}}); await h.settle();
  assert.equal(h.indicator.dataset.state, 'unknown');
  h.intervals.find(timer => timer.ms === 25000).fn(); await h.settle();
  assert.equal(h.requests.length, 1);
});

test('page closure releases presence and browser back-forward restoration renews it', async () => {
  const h = harness(); await h.settle(); h.event('pagehide');
  assert.equal(h.beacons[0].body.get('active'), '0');
  h.intervals.find(timer => timer.ms === 25000).fn(); await h.settle(); assert.equal(h.requests.length, 1);
  h.event('pageshow', {persisted: true}); await h.settle(); assert.equal(h.requests.length, 2);
});

test('late poll response cannot overwrite more recent presence state', async () => {
  const h = harness(); await h.settle();
  h.event('pk:presence', {detail: {'12': {state: 'offline', checked_at: 100002, valid_for_seconds: 75}}});
  h.event('pk:presence', {detail: {'12': {state: 'online', checked_at: 100001, valid_for_seconds: 75}}});
  assert.equal(h.label.textContent, 'Hors ligne');
});

test('browser connection status is not confused with interlocutor presence', async () => {
  const h = harness(); await h.settle(); h.navigator.onLine = false; h.event('offline');
  assert.equal(h.label.textContent, 'Présence indisponible');
  h.event('pk:presence', {detail: {'12': {state: 'online', checked_at: 100002, valid_for_seconds: 75}}});
  assert.equal(h.indicator.dataset.state, 'unknown');
});
