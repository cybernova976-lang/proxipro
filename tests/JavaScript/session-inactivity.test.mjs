import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../../public/js/session-inactivity.js', import.meta.url), 'utf8');
const flush = async () => { for (let i = 0; i < 8; i++) await Promise.resolve(); };

async function page() {
  let clock = 10000000;
  let serverLast = clock;
  const calls = [], timers = [], events = {}, beacons = [];
  const document = {
    hidden: false, documentElement: {style: {}},
    querySelector: selector => selector.includes('data-session')
      ? {dataset: {remaining: '3600', activityUrl: '/auth/session-activity', loginUrl: '/login'}} : {content: 'csrf'},
    addEventListener: (name, handler) => { events[name] = handler; }
  };
  const window = {location: {replace: url => { window.redirected = url; }},
    addEventListener: (name, handler) => { events[name] = handler; }};
  const navigator = {onLine: true, sendBeacon: (url, body) => { beacons.push({url, data: body.values}); return true; }};
  const fetch = async (url, options) => {
    calls.push({url, ...options});
    if (clock - serverLast >= 3600000) return {status: 401, ok: false};
    if (options.method === 'POST') serverLast = Math.max(serverLast, clock - JSON.parse(options.body).age_ms);
    return {status: 200, ok: true, json: async () => ({remaining_seconds: Math.floor((serverLast + 3600000 - clock) / 1000)})};
  };
  runInNewContext(source, {document, window, navigator, fetch, Date: {now: () => clock},
    AbortController, setTimeout: () => 1, clearTimeout() {},
    setInterval: (fn, ms) => timers.push({fn, ms}),
    FormData: class { values = {}; set(key, value) { this.values[key] = value; } }
  });
  await flush();
  return {document, window, navigator, calls, beacons,
    advance(ms) {clock += ms;},
    otherTabInteraction() {serverLast = clock;},
    async event(name, trusted = true) {events[name]({isTrusted: trusted}); await flush();},
    async tick(ms) {timers.find(timer => timer.ms === ms).fn(); await flush();}
  };
}

test('initialization, background polling and visibility never count as interactions', async () => {
  const p = await page();
  p.advance(1800000);
  await p.tick(30000);
  await p.event('visibilitychange');
  await p.event('pointermove', false);
  assert.ok(p.calls.every(call => call.method === 'GET'));
  p.advance(1800000);
  await p.tick(1000);
  assert.equal(p.window.redirected, '/login');
  assert.equal(p.document.documentElement.style.visibility, 'hidden');
});

test('trusted interaction is reported and rapid events are batched with their real age', async () => {
  const p = await page();
  await p.event('pointerdown');
  p.advance(1000);
  await p.event('keydown');
  assert.equal(p.calls.filter(call => call.method === 'POST').length, 1);
  const beforeWait = p.calls.length;
  await p.tick(1000);
  assert.equal(p.calls.length, beforeWait, 'a queued interaction must not trigger polling every second');
  p.advance(14000);
  await p.tick(1000);
  const posts = p.calls.filter(call => call.method === 'POST');
  assert.equal(posts.length, 2);
  assert.equal(JSON.parse(posts[1].body).age_ms, 14000);
});

test('an inactive tab consults the shared server deadline instead of logging out an active tab', async () => {
  const p = await page();
  p.advance(3500000);
  p.otherTabInteraction();
  p.advance(100000);
  await p.tick(1000);
  assert.equal(p.window.redirected, undefined);
  assert.ok(p.calls.every(call => call.method === 'GET'));
});

test('first interaction after sleep cannot revive an expired session', async () => {
  const p = await page();
  p.advance(3601000);
  await p.event('pointerdown');
  assert.equal(p.window.redirected, '/login');
  assert.ok(p.calls.every(call => call.method === 'GET'));
});

test('closing sends only pending genuine activity, not a new activity timestamp', async () => {
  const p = await page();
  await p.event('pagehide');
  assert.equal(p.beacons.length, 0);
  await p.event('pointerdown');
  p.advance(1000);
  await p.event('input');
  p.advance(2000);
  await p.event('pagehide');
  assert.equal(p.beacons[0].data.age_ms, '2000');
});

test('private page is concealed when idle deadline passes offline', async () => {
  const p = await page();
  p.navigator.onLine = false;
  p.advance(3600000);
  await p.tick(1000);
  assert.equal(p.window.redirected, '/login');
  assert.equal(p.document.documentElement.style.visibility, 'hidden');
});
