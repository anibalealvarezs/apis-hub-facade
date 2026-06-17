<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FetchBcvRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aborts_if_revised_rate_exists_for_today()
    {
        ExchangeRate::create([
            'rate' => 36.50,
            'is_revised' => true,
            'source' => 'manual',
            'created_at' => Carbon::today(),
        ]);

        Http::fake();

        $this->artisan('bcv:fetch')
            ->expectsOutput('A revised rate already exists for today. Scraper stopped.')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_it_fetches_and_saves_new_rate()
    {
        $htmlContent = '<html><body><div id="dolar"><strong class="strong-tb"> 36,12345 </strong></div></body></html>';
        
        Http::fake([
            'bcv.org.ve/*' => Http::response($htmlContent, 200)
        ]);

        $this->artisan('bcv:fetch')
            ->expectsOutput('New BCV Rate stored: 36.12345')
            ->assertExitCode(0);

        $this->assertDatabaseHas('exchange_rates', [
            'rate' => 36.12345,
            'is_revised' => false,
            'source' => 'scraper'
        ]);
    }

    public function test_it_does_not_save_duplicate_rates()
    {
        ExchangeRate::create([
            'rate' => 36.12345,
            'is_revised' => false,
            'source' => 'scraper',
        ]);

        $htmlContent = '<html><body><div id="dolar"><strong class="strong-tb"> 36,12345 </strong></div></body></html>';
        
        Http::fake([
            'bcv.org.ve/*' => Http::response($htmlContent, 200)
        ]);

        $this->artisan('bcv:fetch')
            ->expectsOutput('BCV Rate is unchanged: 36.12345')
            ->assertExitCode(0);

        $this->assertEquals(1, ExchangeRate::count());
    }

    public function test_it_handles_failed_http_request()
    {
        Http::fake([
            'bcv.org.ve/*' => Http::response('', 500)
        ]);

        $this->artisan('bcv:fetch')
            ->expectsOutput('Failed to connect to bcv.org.ve')
            ->assertExitCode(0);

        $this->assertEquals(0, ExchangeRate::count());
    }

    public function test_it_handles_regex_failure()
    {
        $htmlContent = '<html><body><div id="something_else"><strong class="strong-tb"> 36,12345 </strong></div></body></html>';
        
        Http::fake([
            'bcv.org.ve/*' => Http::response($htmlContent, 200)
        ]);

        $this->artisan('bcv:fetch')
            ->expectsOutput('Regex did not match the USD rate.')
            ->assertExitCode(0);

        $this->assertEquals(0, ExchangeRate::count());
    }
}
