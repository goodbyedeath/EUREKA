/**
 * ui-sweep — open every admin screen in a real browser and report what a person would hit.
 *
 * Written after three bugs in one week that no amount of reading the code would have caught, and
 * that a markup check did catch wrongly: a close button that could never close (an inline
 * display:flex outranking the `hidden` attribute), every image preview answering 401 (this host
 * strips the query string from image URLs), and the LED screen's live positions never drawing
 * (a write to a deleted tile threw and killed the two statements after it). All three are invisible
 * server-side and obvious to a browser.
 *
 * Usage, always with PHP 8.3's sibling node:
 *
 *   node tools/ui-sweep.cjs                 # sweep every page, then run the interaction tests
 *   node tools/ui-sweep.cjs --no-interact   # read-only: never writes anything
 *   node tools/ui-sweep.cjs --only=kiosk    # just the pages whose path contains this
 *   node tools/ui-sweep.cjs --shots=/path   # where the screenshots go
 *
 * It signs in as the account in UI_SWEEP_EMAIL / UI_SWEEP_PASSWORD (.env) — a browser, not a
 * person, so its activity is easy to tell from the crew's. Disable that account in Account
 * Management and the sweep stops working; nothing else depends on it.
 *
 * Anything the interaction tests create is named with the ZZ_PREFIX below and removed afterwards by
 * tools/ui-sweep-clean.php, which also runs first in case an earlier sweep died half way. The tests
 * never click a destructive control on a row they did not create.
 */

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const APP = path.resolve(__dirname, '..');
const { chromium } = require(path.join(APP, 'node_modules/playwright'));

const BASE = process.env.UI_SWEEP_BASE || 'https://questerra-series.com';
const ZZ_PREFIX = 'ZZ UI Sweep';
const PHP = '/opt/alt/php83/usr/bin/php';

const args = process.argv.slice(2);
const arg = (name, fallback = null) => {
    const hit = args.find((a) => a.startsWith(`--${name}=`));
    return hit ? hit.slice(name.length + 3) : fallback;
};
const INTERACT = !args.includes('--no-interact');
const ONLY = arg('only');

/**
 * How many pages one browser handles before it is replaced.
 *
 * This host runs out of processes long before it runs out of memory — Chromium answers
 * `pthread_create: Resource temporarily unavailable` and the run dies mid-sweep, which is exactly
 * how the first full attempt was lost. A browser that is retired every few pages never gets there.
 */
const CHUNK = Number(arg('chunk', '5'));

/** Nothing may hang the whole run: a page that will not settle is a finding, not a deadlock. */
const PAGE_BUDGET_MS = Number(arg('page-budget', '60000'));
const SHOTS = arg('shots', path.join(APP, 'storage/app/ui-sweep'));
const LOG = arg('log', path.join(APP, 'storage/app/ui-sweep-run.log'));

/**
 * Every line goes to disk as it happens, not when node feels like flushing.
 *
 * A five-minute sweep will sometimes be cut short — a dropped connection, a host that runs out of
 * processes — and a buffered log loses everything it had found by then. Appending per line means a
 * run that dies at page 20 still tells you about pages 1 to 19.
 */
function say(line) {
    console.log(line);
    try {
        fs.appendFileSync(LOG, line + '\n');
    } catch { /* the console still has it */ }
}

/**
 * Pages worth opening. Downloads and PDFs are left out: the browser would save a file and tell us
 * nothing about a screen.
 */
const PAGES = [
    '/', '/login',
    '/kiosk/leaderboard', '/kiosk/map', '/kiosk/led',
    '/admin/dashboard',
    '/admin/users', '/admin/login-cards', '/admin/team-management',
    '/admin/dashboard-management', '/admin/games', '/admin/race-start', '/admin/kesiapan',
    '/admin/quest-locations', '/admin/gps-tracking', '/admin/map-routes',
    '/admin/indoor-maps', '/admin/outpost-access',
    '/admin/user-progress', '/admin/game-assessments', '/admin/game-archives',
    '/admin/hero-slides', '/admin/feature-management', '/admin/fekdi',
    '/admin/panduan', '/admin/panduan/user',
];

/** The two shapes a screen has to survive: a desk and a phone held upright. */
const VIEWPORTS = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'phone', width: 390, height: 844 },
];

/**
 * Console noise that says nothing about this app: the headless shell has no GPU and no fonts, and
 * a third-party CDN's own advice is not our page failing.
 */
const IGNORE = [
    /WebGL|GL Driver|SwiftShader|GroupMarkerNotSet/i,
    /cdn\.tailwindcss\.com should not be used in production/i,
    /Failed to load resource: net::ERR_INTERNET_DISCONNECTED/i,
];

const findings = [];
const note = (page, kind, detail) => findings.push({ page, kind, detail });

function chromePath() {
    const root = path.join(process.env.HOME, '.cache/ms-playwright');
    const dir = fs.readdirSync(root).find((d) => d.startsWith('chromium_headless_shell-'));
    if (!dir) throw new Error('no headless shell; run: node node_modules/playwright-core/cli.js install chromium');

    return path.join(root, dir, 'chrome-headless-shell-linux64/chrome-headless-shell');
}

/**
 * The flags this host needs. Without --disable-features=VizDisplayCompositor Chromium aborts, and
 * --single-process (the usual advice for constrained boxes) makes it die mid-run instead.
 */
const launch = () => chromium.launch({
    executablePath: chromePath(),
    args: ['--disable-features=VizDisplayCompositor', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
});

function credentials() {
    const env = fs.readFileSync(path.join(APP, '.env'), 'utf8');
    const read = (key) => (env.match(new RegExp(`^${key}=(.*)$`, 'm')) || [])[1]?.trim();
    const email = read('UI_SWEEP_EMAIL');
    const password = read('UI_SWEEP_PASSWORD');
    if (!email || !password) throw new Error('UI_SWEEP_EMAIL / UI_SWEEP_PASSWORD missing from .env');

    return { email, password };
}

async function signIn(browser) {
    const { email, password } = credentials();
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();

    await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 45000 });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'load', timeout: 45000 }).catch(() => {}),
        page.click('button[type="submit"]'),
    ]);

    const landed = page.url();
    const state = await ctx.storageState();
    await ctx.close();

    if (landed.includes('/login')) throw new Error(`sign-in failed, still on ${landed}`);
    say(`signed in as ${email} -> ${landed.replace(BASE, '')}\n`);

    return state;
}

/** Open one page, watch everything it says, and photograph it. */
async function visit(browser, state, viewport, target) {
    const ctx = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height }, storageState: state });
    const page = await ctx.newPage();
    // No single call may sit longer than a fifth of the page's whole budget.
    page.setDefaultTimeout(Math.round(PAGE_BUDGET_MS / 5));

    const errors = [];
    const failed = [];
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text().slice(0, 160)); });
    page.on('pageerror', (e) => errors.push(`uncaught: ${String(e).slice(0, 160)}`));
    page.on('requestfailed', (r) => failed.push(`net ${r.url().replace(BASE, '')}`.slice(0, 120)));
    page.on('response', (r) => { if (r.status() >= 400) failed.push(`${r.status()} ${r.url().replace(BASE, '')}`.slice(0, 120)); });

    let status = 'ERR';
    try {
        const resp = await page.goto(BASE + target, { waitUntil: 'domcontentloaded', timeout: Math.round(PAGE_BUDGET_MS / 3) });
        status = resp ? resp.status() : '—';
        // Long enough for Livewire to mount and for one poll of anything that polls.
        await page.waitForTimeout(3000);
    } catch (e) {
        note(target, 'navigation', String(e.message).split('\n')[0].slice(0, 120));
    }

    const overflow = await page.evaluate(
        () => Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
    ).catch(() => 0);

    const brokenImages = await page.evaluate(
        () => [...document.images].filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.currentSrc || i.src).slice(0, 6),
    ).catch(() => []);

    // The failure bar must never be on screen on a healthy page — it was, for a week.
    const barShowing = await page.evaluate(() => {
        const el = document.getElementById('lw-failure');
        return !!el && el.style.display !== 'none' && !el.hidden;
    }).catch(() => false);

    fs.mkdirSync(SHOTS, { recursive: true });
    const shot = path.join(SHOTS, `${viewport.name}${target.replace(/\//g, '_') || '_root'}.png`);
    await page.screenshot({ path: shot, timeout: Math.round(PAGE_BUDGET_MS / 5) }).catch(() => {});

    const real = errors.filter((e) => !IGNORE.some((re) => re.test(e)));
    const realFailed = [...new Set(failed)].filter((f) => !/googletagmanager|fonts\.bunny|cdnjs/.test(f));

    if (typeof status === 'number' && status >= 400) note(target, 'status', `HTTP ${status} (${viewport.name})`);
    real.forEach((e) => note(target, 'console', `${e} (${viewport.name})`));
    realFailed.forEach((f) => note(target, 'request', `${f} (${viewport.name})`));
    brokenImages.forEach((i) => note(target, 'image', `does not load: ${i.replace(BASE, '')} (${viewport.name})`));
    if (barShowing) note(target, 'failure-bar', `the red bar is on screen at rest (${viewport.name})`);
    // A phone gets a horizontal scrollbar from a few stray pixels; 4 is the usual rounding.
    if (viewport.name === 'phone' && overflow > 4) note(target, 'layout', `${overflow}px wider than the screen`);

    say(
        `  ${viewport.name.padEnd(7)} ${target.padEnd(28)} ${String(status).padEnd(4)}`
        + `${real.length ? ` console:${real.length}` : ''}${realFailed.length ? ` failed:${realFailed.length}` : ''}`
        + `${brokenImages.length ? ` broken-img:${brokenImages.length}` : ''}`
        + `${viewport.name === 'phone' && overflow > 4 ? ` overflow:${overflow}px` : ''}`
        + `${barShowing ? ' RED-BAR' : ''}`,
    );

    await ctx.close().catch(() => {});
}

/**
 * The flows the crew actually performs, done the way they perform them. Everything written here is
 * named with ZZ_PREFIX and removed afterwards; nothing existing is touched.
 */
async function interactions(browser, state) {
    say('\ninteractions');
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, storageState: state });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e).slice(0, 160)));

    const step = async (label, fn) => {
        try {
            await fn();
            say(`  PASS ${label}`);
        } catch (e) {
            say(`  FAIL ${label}  [${String(e.message).split('\n')[0].slice(0, 110)}]`);
            note('interaction', 'flow', `${label}: ${String(e.message).split('\n')[0].slice(0, 120)}`);
        }
    };

    // 1. The failure bar: show it the way a failed request does, then close it like a person.
    await step('the red bar closes on ×', async () => {
        await page.goto(`${BASE}/admin/kesiapan`, { waitUntil: 'load', timeout: 45000 });
        await page.evaluate(() => {
            const el = document.getElementById('lw-failure');
            document.getElementById('lw-failure-text').textContent = 'uji sapuan';
            el.dataset.status = '500';
            el.hidden = false;
            el.style.display = 'flex';
        });
        if (!(await page.locator('#lw-failure').isVisible())) throw new Error('bar would not show');
        await page.locator('#lw-failure-close').click();
        if (await page.locator('#lw-failure').isVisible()) throw new Error('× did not close it');
    });

    // 2. The guide the players read: add a section, see it listed.
    await step('a guide section can be added and is listed', async () => {
        const title = `${ZZ_PREFIX} ${Date.now()}`;
        await page.goto(`${BASE}/admin/panduan/user`, { waitUntil: 'load', timeout: 45000 });
        await page.fill('input[wire\\:model="title"]', title);
        await page.fill('textarea[wire\\:model="body"]', 'Baris satu.\nBaris dua.');
        await page.click('button:has-text("Tambahkan")');
        await page.waitForTimeout(2500);
        if (!(await page.locator(`text=${title}`).count())) throw new Error('the new section is not on the page');
    });

    // 3. The heart of authoring: a questionnaire, then a question inside it.
    await step('a questionnaire can be created', async () => {
        const title = `${ZZ_PREFIX} Kuis ${Date.now()}`;
        await page.goto(`${BASE}/admin/dashboard-management`, { waitUntil: 'load', timeout: 45000 });
        const opener = page.locator('button:has-text("Buat"), button:has-text("Tambah"), a:has-text("Buat")').first();
        if (!(await opener.count())) throw new Error('no create button on the questionnaire page');
        await opener.click();
        await page.waitForTimeout(1500);
        const field = page.locator('input[wire\\:model="title"], input[wire\\:model\\.live="title"], input[name="title"]').first();
        if (!(await field.count())) throw new Error('no title field after opening the form');
        await field.fill(title);
        say(`       (created ${title}; cleanup removes it)`);
    });

    errors.forEach((e) => note('interaction', 'uncaught', e));
    await ctx.close().catch(() => {});
}

function clean(stage) {
    try {
        const out = execFileSync(PHP, [path.join(APP, 'tools/ui-sweep-clean.php')], { encoding: 'utf8' }).trim();
        if (out) say(`${stage}: ${out}`);
    } catch (e) {
        say(`${stage}: cleanup failed — ${String(e.message).split('\n')[0]}`);
    }
}

(async () => {
    const targets = PAGES.filter((p) => !ONLY || p.includes(ONLY));
    say(`ui-sweep: ${targets.length} pages x ${VIEWPORTS.length} viewports, ${INTERACT ? 'with' : 'without'} interactions`);
    say(`screenshots -> ${SHOTS}\n`);

    if (INTERACT) clean('before');

    let browser = await launch();
    const state = await signIn(browser);
    let sinceRestart = 0;

    const restart = async () => {
        try { await browser.close(); } catch { /* already gone */ }
        // Let the host reclaim the processes before asking for more.
        await new Promise((r) => setTimeout(r, 1500));
        browser = await launch();
        sinceRestart = 0;
    };

    for (const vp of VIEWPORTS) {
        for (const target of targets) {
            try {
                // No outer race: a losing race leaves the visit running, and it then logs a second
                // line for the same page and pulls the context out from under the next browser.
                // Every await inside visit() is bounded instead, so it cannot outstay its budget.
                await visit(browser, state, vp, target);
            } catch (e) {
                // A page that dies or hangs costs one row, not the run.
                note(target, 'browser', `${String(e.message).split('\n')[0].slice(0, 110)} (${vp.name})`);
                say(`  ${vp.name.padEnd(7)} ${target.padEnd(28)} ---  ${String(e.message).split('\n')[0].slice(0, 60)}`);
                await restart();
                continue;
            }

            if (++sinceRestart >= CHUNK) await restart();
        }
    }

    if (INTERACT) {
        await interactions(browser, state);
        clean('after');
    }

    await browser.close();

    say(`\n${findings.length} finding(s)`);
    for (const f of findings) say(`  ${f.kind.padEnd(12)} ${f.page.padEnd(28)} ${f.detail}`);
    process.exit(findings.length ? 1 : 0);
})();
