import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

test('widget background retries stop after two automatic reloads', () => {
    const timers = [];
    const window = {};
    runInNewContext(readFileSync(new URL('../../public/js/dashboard-renderer.js', import.meta.url), 'utf8'), {
        window, console, setTimeout: callback => { timers.push(callback); return timers.length; }, clearTimeout() {},
    });
    const renderer = window.dashboardRenderer;
    let calls = 0;
    const container = { isConnected: true, innerHTML: '', addEventListener() {}, removeEventListener() {} };
    renderer.errorState = message => message;
    renderer.loadingSkeleton = () => 'loading';
    renderer.renderWidget = () => { calls++; renderer._scheduleRetry('test', container, {}, 'test-tenant'); };
    renderer._scheduleRetry('test', container, {}, 'test-tenant');
    timers[0]();
    timers[1]();
    assert.equal(calls, 2);
    assert.equal(timers.length, 2);
    assert.match(container.innerHTML, /Automatic retries stopped/);
    assert.equal(renderer._retryTimers.size, 0);
});
