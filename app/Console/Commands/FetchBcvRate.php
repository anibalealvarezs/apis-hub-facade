<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchBcvRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bcv:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the latest USD exchange rate from BCV website';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Check if a revised rate already exists for today.
        $revisedToday = ExchangeRate::whereDate('created_at', Carbon::today())
            ->where('is_revised', true)
            ->exists();

        if ($revisedToday) {
            $this->info('A revised rate already exists for today. Scraper stopped.');
            return;
        }

        // 2. Scrape BCV website
        try {
            // Provide a fallback timeout and verify=>false for broken SSL if needed
            $response = Http::timeout(10)->withoutVerifying()->get('https://www.bcv.org.ve/');
            
            if ($response->failed()) {
                Log::error('BCV Scraper failed to fetch the website.');
                $this->error('Failed to connect to bcv.org.ve');
                return;
            }

            $html = $response->body();

            // The BCV rate for USD is typically in an element: <div id="dolar">... <strong> 36,50123 </strong>
            if (preg_match('/<div id="dolar".*?<strong>\s*([0-9,]+)\s*<\/strong>/is', $html, $matches)) {
                $rateString = trim($matches[1]);
                $rateValue = (float) str_replace(',', '.', $rateString);

                if ($rateValue > 0) {
                    // Check if it's the same as the last rate
                    $lastRate = ExchangeRate::latest('id')->first();
                    
                    if (!$lastRate || $lastRate->rate != $rateValue) {
                        ExchangeRate::create([
                            'rate' => $rateValue,
                            'is_revised' => false,
                            'source' => 'scraper'
                        ]);
                        $this->info("New BCV Rate stored: {$rateValue}");
                    } else {
                        $this->info("BCV Rate is unchanged: {$rateValue}");
                    }
                } else {
                    $this->error("Parsed rate is zero or invalid.");
                }
            } else {
                $this->error("Regex did not match the USD rate.");
            }

        } catch (\Exception $e) {
            Log::error("BCV Scraper exception: " . $e->getMessage());
            $this->error("Exception: " . $e->getMessage());
        }
    }
}
