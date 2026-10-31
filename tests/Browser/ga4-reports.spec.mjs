import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
const component = execFileSync('php', ['-r', `require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo Illuminate\\Support\\Facades\\Blade::render('<x-ga4-report-workspace :report-dashboards="$dashboards" />', ['dashboards' => [7 => 'Marketing']]);`], { encoding: 'utf8' });
const reports = readFileSync('resources/js/dashboards/ga4-reports.js', 'utf8').replace('export function', 'function');
const alpine = readFileSync('node_modules/alpinejs/dist/cdn.min.js', 'utf8');

test('source-window users retain their total and can be saved without a daily rollup', async ({ page }) => {
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    const requests = [];
    await page.route('http://ga4.test/**', async route => {
        if (route.request().method() === 'GET') return route.fulfill({ contentType: 'text/html', body: `<style>[x-cloak]{display:none!important}</style><main x-data="{tenantId:1,csrfToken:'fixture',account:'42',dateStart:'2026-10-01',dateEnd:'2026-10-30'}">${component}</main>` });
        const payload = route.request().postDataJSON(); requests.push({ path: new URL(route.request().url()).pathname, payload });
        const catalog = { context: { property_id:'123', time_zone:'America/Caracas', currency:'USD' }, metrics: {totalUsers:{raw_name:'totalUsers',label:'Total users',implemented:true,unit:'users'}}, dimensions:{date:{raw_name:'date',label:'Date'}}, recipes:{} };
        const report = { state:'ready', definition:{kind:'window',field_definitions:{metrics:{totalUsers:{uiName:'Total users'}}}}, result:{response:{dimensionHeaders:[],metricHeaders:[{name:'totalUsers'}],rows:[{metricValues:[{value:'5'}]}],metadata:{timeZone:'America/Caracas'}},quality:{state:'source_metadata_available',details:{timeZone:'America/Caracas'}}} };
        return route.fulfill({ json: route.request().url().endsWith('/catalog') ? {success:true,data:{state:'ready',catalog}} : route.request().url().endsWith('/report-widget') ? {success:true,widget_id:9} : {success:true,data:report} });
    });
    await page.goto('http://ga4.test/');
    await page.addScriptTag({content:reports}); await page.addScriptTag({content:alpine});
    await page.getByRole('button',{name:'Load property catalog'}).click();
    await expect(page.getByRole('option',{name:'Total users',exact:true})).toBeAttached();
    await page.getByRole('button',{name:'Request report',exact:true}).click();
    await expect(page.getByRole('cell',{name:'5',exact:true})).toBeVisible();
    await page.getByPlaceholder('Widget title').fill('Whole-window users');
    await page.getByRole('combobox',{name:'Dashboard',exact:true}).selectOption('7');
    await page.getByRole('button',{name:'Add report widget'}).click();
    await expect(page.getByText('Report added to the selected dashboard.')).toBeVisible();
    const query = requests.find(request => request.path.endsWith('/scoped-report')).payload;
    expect(query.definition.dimensions).toEqual([]); expect(query.definition.metrics).toEqual(['totalUsers']);
    expect(query.definition.start_date).toBe('2026-10-01');
    expect(requests.find(request => request.path.endsWith('/report-widget')).payload.definition).toEqual(query.definition);
    await page.evaluate(() => { window.Alpine.$data(document.querySelector('main')).account = '43'; });
    await expect(page.getByRole('button',{name:'Request report',exact:true})).toBeHidden();
    expect(errors).toEqual([]);
});


test('saved source cards preserve one window value without daily aggregation', async ({ page }) => {
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    await page.setContent('<div id="widget"></div>');
    await page.addScriptTag({ content: readFileSync('public/js/dashboard-renderer.js', 'utf8') });
    await page.evaluate(() => window.dashboardRenderer.render(document.querySelector('#widget'), {
        widget_type: 'tile', controls: {}, data: {scoped_report: {state:'ready', definition:{kind:'window',context:{currency:'USD'},field_definitions:{metrics:{totalUsers:{uiName:'Total users',unit:'users'}}}}, result:{response:{dimensionHeaders:[],metricHeaders:[{name:'totalUsers'}],rows:[{metricValues:[{value:'5'}]}]},quality:{state:'unknown'}}}}
    }));
    await expect(page.locator('#widget').getByText('Total users',{exact:true}).first()).toBeVisible();
    await expect(page.locator('#widget').getByText('5',{exact:true}).first()).toBeVisible();
    await expect(page.locator('#widget table')).toBeHidden();
    expect(errors).toEqual([]);
});
