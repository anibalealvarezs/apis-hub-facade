import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../resources/js/livewire-resilience.js', import.meta.url), 'utf8');
const { installLivewireResilience } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('gateway failures suppress modal, back off polls, and recover without replaying actions', () => {
    let hook, fail, succeed;
    const timers = [];
    const attrs = new Map([['wire:poll.30s', '']]);
    const element = {
        isConnected: true,
        get attributes() { return [...attrs].map(([name, value]) => ({ name, value })); },
        removeAttribute: name => attrs.delete(name),
        setAttribute: (name, value) => attrs.set(name, value),
    };
    let notices = 0;
    const document = {
        querySelectorAll: () => [element], documentElement: { lang: 'en' },
        createElement: () => ({ setAttribute() {}, style: {}, remove() { notices--; } }),
        body: { appendChild() { notices++; } },
    };
    installLivewireResilience({ hook: (_, callback) => { hook = callback; } }, document, {
        clearTimeout() {}, setTimeout(callback, delay) { timers.push({ callback, delay }); },
    });
    hook({ fail: callback => { fail = callback; }, succeed: callback => { succeed = callback; } });
    let prevented = 0;
    const failure = status => fail({ status, preventDefault() { prevented++; } });
    failure(520);
    assert.equal(prevented, 1);
    assert.equal(attrs.size, 0);
    succeed(); // Another component completing must not erase the cooldown.
    assert.equal(attrs.size, 0);
    failure(520);
    assert.equal(notices, 1);
    assert.ok(timers[1].delay >= 60000);
    timers[1].callback();
    assert.equal(attrs.has('wire:poll.30s'), true);
    succeed();
    assert.equal(notices, 0);
    failure(419);
    failure(403);
    assert.equal(prevented, 2);
});
