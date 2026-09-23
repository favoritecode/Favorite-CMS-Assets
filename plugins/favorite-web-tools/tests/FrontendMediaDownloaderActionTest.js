/**
 * Automated Frontend Action Bridge & Template Rendering Tests
 * Tests A-Q for Favorite Web Tools v1.3.0 Media Downloader
 */

const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Load tools-frontend.js source code
const jsCode = fs.readFileSync(path.join(__dirname, '../assets/js/tools-frontend.js'), 'utf8');

// Minimal DOM Mock for Node VM environment
function createMockDomEnvironment() {
    const listeners = {};

    class MockNode {
        constructor(tagName = 'div', attributes = {}) {
            this.tagName = tagName.toUpperCase();
            this.attributes = { ...attributes };
            this.children = [];
            this.parentNode = null;
            this._text = '';
            this._html = '';
            this.style = {};
            this.dataset = {};
            this.options = [];
            this.selectedIndex = 0;
            this.value = '';
            this.disabled = false;
        }

        get textContent() {
            return this._text;
        }
        set textContent(val) {
            this._text = String(val || '');
            this._html = this._text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        get innerHTML() {
            return this._html;
        }
        set innerHTML(val) {
            this._html = String(val || '');
            this._text = this._html;
        }

        setAttribute(k, v) {
            this.attributes[k] = String(v);
            if (k.startsWith('data-')) {
                const dataKey = k.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase());
                this.dataset[dataKey] = String(v);
            }
        }

        getAttribute(k) {
            return this.attributes[k] !== undefined ? this.attributes[k] : null;
        }

        removeAttribute(k) {
            delete this.attributes[k];
        }

        click() {}

        appendChild(child) {
            child.parentNode = this;
            this.children.push(child);
            return child;
        }

        removeChild(child) {
            const idx = this.children.indexOf(child);
            if (idx !== -1) {
                this.children.splice(idx, 1);
                child.parentNode = null;
            }
            return child;
        }

        querySelector(sel) {
            const results = this.querySelectorAll(sel);
            return results.length > 0 ? results[0] : null;
        }

        querySelectorAll(sel) {
            const matched = [];
            function traverse(node) {
                for (const child of node.children) {
                    if (matchesSelector(child, sel)) {
                        matched.push(child);
                    }
                    traverse(child);
                }
            }
            traverse(this);
            return matched;
        }

        closest(sel) {
            let cur = this;
            while (cur) {
                if (matchesSelector(cur, sel)) {
                    return cur;
                }
                cur = cur.parentNode;
            }
            return null;
        }
    }

    function matchesSelector(node, sel) {
        const parts = sel.split(',').map(s => s.trim());
        return parts.some(p => {
            if (p.startsWith('.')) {
                const cls = p.slice(1);
                const classAttr = node.getAttribute('class') || '';
                return classAttr.split(/\s+/).includes(cls);
            }
            if (p.startsWith('#')) {
                const id = p.slice(1);
                return node.getAttribute('id') === id;
            }
            if (p.startsWith('[') && p.endsWith(']')) {
                const attrExpr = p.slice(1, -1);
                if (attrExpr.includes('=')) {
                    const [k, rawVal] = attrExpr.split('=');
                    const expected = rawVal.replace(/^["']|["']$/g, '');
                    return node.getAttribute(k.trim()) === expected;
                }
                return node.getAttribute(attrExpr.trim()) !== null;
            }
            if (p.toUpperCase() === node.tagName) {
                return true;
            }
            return false;
        });
    }

    const documentMock = {
        createElement: (tag) => new MockNode(tag),
        getElementById: (id) => null,
        addEventListener: (event, handler) => {
            listeners[event] = listeners[event] || [];
            listeners[event].push(handler);
        },
        dispatchEvent: (event) => {
            const handlers = listeners[event.type] || [];
            for (const h of handlers) h(event);
        },
        querySelector: (sel) => null,
        querySelectorAll: (sel) => [],
        body: new MockNode('body')
    };

    const windowMock = {
        document: documentMock,
        addEventListener: documentMock.addEventListener,
        fetch: null,
        renderTemplateSafe: undefined,
        FavoriteWebTools: {}
    };

    function createSandbox() {
        return {
            window: windowMock,
            document: documentMock,
            fetch: (url, opts) => (windowMock.fetch ? windowMock.fetch(url, opts) : Promise.resolve({ ok: true, json: async () => ({}) })),
            setTimeout,
            clearTimeout,
            console
        };
    }

    return { windowMock, documentMock, listeners, MockNode, createSandbox };
}

test('Test 1: renderTemplateSafe is immediately defined on evaluation without form in DOM', () => {
    const { windowMock, documentMock } = createMockDomEnvironment();
    const sandbox = {
        window: windowMock,
        document: documentMock,
        setTimeout: setTimeout,
        clearTimeout: clearTimeout,
        console: console
    };

    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    assert.strictEqual(typeof windowMock.renderTemplateSafe, 'function');
});

test('Test 2 (Test C): renderTemplateSafe produces single card with correct data-fwt-url', () => {
    const { windowMock, documentMock } = createMockDomEnvironment();
    const sandbox = { window: windowMock, document: documentMock, setTimeout, clearTimeout, console };
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    const cardTmpl = '<div class="fwt-media-item-card" data-fwt-item-id="{{@index}}" data-fwt-url="{{url}}" data-fwt-status="ready"><span class="fwt-title">{{title}}</span><select data-fwt-select="format">{{#formats}}<option value="{{format_id}}">{{quality}}</option>{{/formats}}</select></div>';

    const itemData = {
        '@index': 0,
        url: 'https://www.youtube.com/watch?v=NHWjhX0Fekk',
        title: 'Top Rotating Background Video',
        formats: [
            { format_id: '137', quality: '1080p' },
            { format_id: '18', quality: '360p' }
        ]
    };

    const html = windowMock.renderTemplateSafe(cardTmpl, itemData);

    assert.ok(html.includes('data-fwt-url="https://www.youtube.com/watch?v=NHWjhX0Fekk"'));
    assert.ok(!html.includes('data-fwt-url=""'));
    assert.ok(html.includes('Top Rotating Background Video'));
    assert.ok(html.includes('value="137"'));
    assert.ok(html.includes('value="18"'));
});

test('Test 3 (Test D): renderTemplateSafe produces 2 cards with distinct URLs and indexes', () => {
    const { windowMock, documentMock } = createMockDomEnvironment();
    const sandbox = { window: windowMock, document: documentMock, setTimeout, clearTimeout, console };
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    const gridTmpl = '{{#items}}<div class="fwt-card" data-fwt-item-id="{{@index}}" data-fwt-url="{{url}}"><h3>{{title}}</h3></div>{{/items}}';

    const bulkData = {
        items: [
            { '@index': 0, url: 'https://www.youtube.com/watch?v=first_video_111', title: 'Video 1' },
            { '@index': 1, url: 'https://www.youtube.com/watch?v=second_video_222', title: 'Video 2' }
        ]
    };

    const html = windowMock.renderTemplateSafe(gridTmpl, bulkData);

    assert.ok(html.includes('data-fwt-item-id="0" data-fwt-url="https://www.youtube.com/watch?v=first_video_111"'));
    assert.ok(html.includes('data-fwt-item-id="1" data-fwt-url="https://www.youtube.com/watch?v=second_video_222"'));
    assert.ok(html.includes('Video 1'));
    assert.ok(html.includes('Video 2'));
});

test('Test 4 (Test N): Strict card context resolution avoids cross-card URL contamination', async () => {
    const { windowMock, documentMock, MockNode, createSandbox } = createMockDomEnvironment();

    let capturedPost = null;
    windowMock.fetch = async (url, options) => {
        if (url === '/api/tools/media-downloader/start-job') {
            capturedPost = JSON.parse(options.body);
            return {
                ok: true,
                json: async () => ({ success: true, jobId: 'job_card2_test_12345' })
            };
        }
        if (url.startsWith('/api/tools/media-downloader/job-status/')) {
            return {
                ok: true,
                json: async () => ({ status: 'ready', pct: 100, filename: 'test.mp4' })
            };
        }
        return { ok: true, json: async () => ({}) };
    };

    const sandbox = createSandbox();
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    // Initialize generic action bridge
    documentMock.dispatchEvent({ type: 'DOMContentLoaded' });

    // Build DOM container with 2 distinct cards
    const container = new MockNode('div', { 'data-fwt-container': 'media-downloader' });

    const card1 = new MockNode('div', {
        'data-fwt-item-id': '0',
        'data-fwt-url': 'https://www.youtube.com/watch?v=card1_should_not_be_used'
    });
    const select1 = new MockNode('select', { 'data-fwt-select': 'format' });
    const opt1 = new MockNode('option', { value: '137', 'data-stream': 'video', 'data-has-audio': '1' });
    opt1.value = '137';
    select1.options = [opt1];
    select1.selectedIndex = 0;
    card1.appendChild(select1);
    const btn1 = new MockNode('button', { 'data-fwt-action': 'fast-download' });
    card1.appendChild(btn1);

    const card2 = new MockNode('div', {
        'data-fwt-item-id': '1',
        'data-fwt-url': 'https://www.youtube.com/watch?v=card2_target_url'
    });
    const select2 = new MockNode('select', { 'data-fwt-select': 'format' });
    const opt2 = new MockNode('option', { value: '18', 'data-stream': 'video', 'data-has-audio': '1' });
    opt2.value = '18';
    select2.options = [opt2];
    select2.selectedIndex = 0;
    card2.appendChild(select2);
    const btn2 = new MockNode('button', { 'data-fwt-action': 'fast-download' });
    card2.appendChild(btn2);

    container.appendChild(card1);
    container.appendChild(card2);

    // Simulate clicking download button on Card 2
    documentMock.dispatchEvent({
        type: 'click',
        target: btn2,
        preventDefault: () => {}
    });

    // Wait for microtasks to resolve
    await new Promise(resolve => setTimeout(resolve, 50));

    assert.ok(capturedPost, 'Fetch start-job was called');
    assert.strictEqual(capturedPost.url, 'https://www.youtube.com/watch?v=card2_target_url');
    assert.notStrictEqual(capturedPost.url, 'https://www.youtube.com/watch?v=card1_should_not_be_used');
    assert.strictEqual(capturedPost.format, '18');
    assert.strictEqual(capturedPost.hasAudio, '1');
    windowMock.FavoriteWebTools.media.download.cancelAll();
});

test('Test 5 (Test O): Controlled error handling when format is missing', async () => {
    const { windowMock, documentMock, MockNode, createSandbox } = createMockDomEnvironment();

    let fetchCalled = false;
    windowMock.fetch = async () => {
        fetchCalled = true;
        return { ok: true, json: async () => ({}) };
    };

    const sandbox = createSandbox();
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    documentMock.dispatchEvent({ type: 'DOMContentLoaded' });

    const card = new MockNode('div', {
        'data-fwt-item-id': '0',
        'data-fwt-url': 'https://www.youtube.com/watch?v=NHWjhX0Fekk'
    });
    const selectEmpty = new MockNode('select', { 'data-fwt-select': 'format' });
    selectEmpty.options = []; // No formats available
    card.appendChild(selectEmpty);

    const errBox = new MockNode('div', { 'data-fwt-bind': 'error-message' });
    card.appendChild(errBox);

    const btn = new MockNode('button', { 'data-fwt-action': 'fast-download' });
    card.appendChild(btn);

    // Click download on formatless card
    documentMock.dispatchEvent({
        type: 'click',
        target: btn,
        preventDefault: () => {}
    });

    await new Promise(resolve => setTimeout(resolve, 50));

    assert.strictEqual(fetchCalled, false, 'Should not attempt start-job when format is completely missing');
    assert.strictEqual(card.getAttribute('data-fwt-status'), 'error');
    assert.strictEqual(errBox.textContent, 'No media format selected or available for download.');
});

test('Test 6 (Test P): Controlled error handling when URL is missing', async () => {
    const { windowMock, documentMock, MockNode, createSandbox } = createMockDomEnvironment();

    let fetchCalled = false;
    windowMock.fetch = async () => {
        fetchCalled = true;
        return { ok: true, json: async () => ({}) };
    };

    const sandbox = createSandbox();
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    documentMock.dispatchEvent({ type: 'DOMContentLoaded' });

    const card = new MockNode('div', {
        'data-fwt-item-id': '0',
        'data-fwt-url': '' // Missing URL
    });
    const errBox = new MockNode('div', { 'data-fwt-bind': 'error-message' });
    card.appendChild(errBox);

    const btn = new MockNode('button', { 'data-fwt-action': 'fast-download' });
    card.appendChild(btn);

    documentMock.dispatchEvent({
        type: 'click',
        target: btn,
        preventDefault: () => {}
    });

    await new Promise(resolve => setTimeout(resolve, 50));

    assert.strictEqual(fetchCalled, false, 'Should not attempt start-job when URL is missing');
    assert.strictEqual(card.getAttribute('data-fwt-status'), 'error');
    assert.strictEqual(errBox.textContent, 'Unable to determine media URL for download.');
});

test('Test 7 (Test Q): Single-mode textarea fallback works when zero card context exists', async () => {
    const { windowMock, documentMock, MockNode, createSandbox } = createMockDomEnvironment();

    let capturedPost = null;
    windowMock.fetch = async (url, options) => {
        if (url === '/api/tools/media-downloader/start-job') {
            capturedPost = JSON.parse(options.body);
            return { ok: true, json: async () => ({ success: true, jobId: 'textarea_job_999' }) };
        }
        if (url.startsWith('/api/tools/media-downloader/job-status/')) {
            return {
                ok: true,
                json: async () => ({ status: 'ready', pct: 100, filename: 'test.mp4' })
            };
        }
        return { ok: true, json: async () => ({}) };
    };

    const sandbox = createSandbox();
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    documentMock.dispatchEvent({ type: 'DOMContentLoaded' });

    // Container without any card, only a textarea and a standalone action button
    const container = new MockNode('div', { 'data-fwt-container': 'media-downloader' });
    const textarea = new MockNode('textarea', { 'data-fwt-input': 'urls' });
    textarea.value = 'https://www.youtube.com/watch?v=standalone_textarea_url';
    container.appendChild(textarea);

    const standaloneBtn = new MockNode('button', {
        'data-fwt-action': 'download',
        'data-fwt-format': '18'
    });
    container.appendChild(standaloneBtn);

    documentMock.dispatchEvent({
        type: 'click',
        target: standaloneBtn,
        preventDefault: () => {}
    });

    await new Promise(resolve => setTimeout(resolve, 50));

    assert.ok(capturedPost, 'Fetch start-job was triggered');
    assert.strictEqual(capturedPost.url, 'https://www.youtube.com/watch?v=standalone_textarea_url');
    assert.strictEqual(capturedPost.format, '18');
    windowMock.FavoriteWebTools.media.download.cancelAll();
});

test('Test 8: Card URL attribute and textarea fallbacks resolve correctly when primary attribute is empty', async () => {
    const { windowMock, documentMock, MockNode, createSandbox } = createMockDomEnvironment();

    let capturedPosts = [];
    windowMock.fetch = async (url, options) => {
        if (url === '/api/tools/media-downloader/start-job') {
            const body = JSON.parse(options.body);
            capturedPosts.push(body);
            return { ok: true, json: async () => ({ success: true, jobId: 'job_' + capturedPosts.length }) };
        }
        if (url.startsWith('/api/tools/media-downloader/job-status/')) {
            return {
                ok: true,
                json: async () => ({ status: 'ready', pct: 100, filename: 'test.mp4' })
            };
        }
        return { ok: true, json: async () => ({}) };
    };

    const sandbox = createSandbox();
    vm.createContext(sandbox);
    vm.runInContext(jsCode, sandbox);

    documentMock.dispatchEvent({ type: 'DOMContentLoaded' });

    // Container with textarea and card having empty data-fwt-url but present data-fwt-source-url
    const container = new MockNode('div', { 'data-fwt-container': 'media-downloader' });
    const textarea = new MockNode('textarea', { 'data-fwt-input': 'urls' });
    textarea.value = 'https://www.youtube.com/watch?v=fallback_url';
    container.appendChild(textarea);

    const card = new MockNode('div', {
        'data-fwt-item-id': '0',
        'data-fwt-url': '',
        'data-fwt-source-url': 'https://www.youtube.com/watch?v=source_attr_url'
    });
    const btn = new MockNode('button', {
        'data-fwt-action': 'fast-download',
        'data-fwt-format': '137'
    });
    card.appendChild(btn);
    container.appendChild(card);

    documentMock.dispatchEvent({
        type: 'click',
        target: btn,
        preventDefault: () => {}
    });

    await new Promise(resolve => setTimeout(resolve, 50));

    assert.strictEqual(capturedPosts.length, 1);
    assert.strictEqual(capturedPosts[0].url, 'https://www.youtube.com/watch?v=source_attr_url');
    windowMock.FavoriteWebTools.media.download.cancelAll();
});
