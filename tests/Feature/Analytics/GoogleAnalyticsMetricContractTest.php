<?php

use App\Http\Controllers\Api\GoogleAnalyticsController;
use App\Models\Project;
use App\Models\User;
use App\Services\Analytics\KpiFormBuilder;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'subdomain' => 'ga-test-' . uniqid(),
        'sync_config' => [
            'google_analytics' => [
                'properties' => [
                    ['id' => '123456', 'enabled' => true, 'lost_access' => false],
                ],
            ],
        ],
    ]);
    Filament::setTenant($this->project, true);

    $this->controller = new GoogleAnalyticsController();
    $this->reflection = new ReflectionClass(GoogleAnalyticsController::class);
    
    $this->mapToGa4 = $this->reflection->getMethod('mapToGa4');
    $this->mapToGa4->setAccessible(true);
    
    $this->mapFromGa4 = $this->reflection->getMethod('mapFromGa4');
    $this->mapFromGa4->setAccessible(true);
});

test('traffic_matrix metric options round-trip symmetrically between form builder and controller response', function () {
    $options = KpiFormBuilder::getMetricOptionsForChannel('google_analytics', 'daily', 'traffic_matrix');
    expect($options)->not->toBeEmpty();

    foreach (array_keys($options) as $canonicalKey) {
        $ga4Key = $this->mapToGa4->invoke($this->controller, $canonicalKey);
        $roundTripKey = $this->mapFromGa4->invoke($this->controller, $ga4Key);
        
        expect($roundTripKey)->toBe($canonicalKey, "Metric [{$canonicalKey}] failed round-trip symmetry. Mapped to [{$ga4Key}], but returned as [{$roundTripKey}].");
    }
});

test('acquisition_matrix metric options round-trip symmetrically between form builder and controller response', function () {
    $options = KpiFormBuilder::getMetricOptionsForChannel('google_analytics', 'daily', 'acquisition_matrix');
    expect($options)->not->toBeEmpty();

    foreach (array_keys($options) as $canonicalKey) {
        $ga4Key = $this->mapToGa4->invoke($this->controller, $canonicalKey);
        $roundTripKey = $this->mapFromGa4->invoke($this->controller, $ga4Key);
        
        expect($roundTripKey)->toBe($canonicalKey, "Metric [{$canonicalKey}] failed round-trip symmetry. Mapped to [{$ga4Key}], but returned as [{$roundTripKey}].");
    }
});

test('event_matrix metric options round-trip symmetrically between form builder and controller response', function () {
    $options = KpiFormBuilder::getMetricOptionsForChannel('google_analytics', 'daily', 'event_matrix');
    expect($options)->not->toBeEmpty();

    foreach (array_keys($options) as $canonicalKey) {
        $ga4Key = $this->mapToGa4->invoke($this->controller, $canonicalKey);
        $roundTripKey = $this->mapFromGa4->invoke($this->controller, $ga4Key);
        
        expect($roundTripKey)->toBe($canonicalKey, "Metric [{$canonicalKey}] failed round-trip symmetry. Mapped to [{$ga4Key}], but returned as [{$roundTripKey}].");
    }
});

test('ad_touchpoint_matrix metric options round-trip symmetrically between form builder and controller response', function () {
    $options = KpiFormBuilder::getMetricOptionsForChannel('google_analytics', 'daily', 'ad_touchpoint_matrix');
    expect($options)->not->toBeEmpty();

    foreach (array_keys($options) as $canonicalKey) {
        $ga4Key = $this->mapToGa4->invoke($this->controller, $canonicalKey);
        $roundTripKey = $this->mapFromGa4->invoke($this->controller, $ga4Key);
        
        expect($roundTripKey)->toBe($canonicalKey, "Metric [{$canonicalKey}] failed round-trip symmetry. Mapped to [{$ga4Key}], but returned as [{$roundTripKey}].");
    }
});

test('every google_analytics metric key across all matrices has perfect key symmetry', function () {
    $matrices = ['traffic_matrix', 'acquisition_matrix', 'event_matrix', 'ad_touchpoint_matrix'];
    $testedKeysCount = 0;
    
    foreach ($matrices as $matrix) {
        $options = KpiFormBuilder::getMetricOptionsForChannel('google_analytics', 'daily', $matrix);
        foreach (array_keys($options) as $canonicalKey) {
            $ga4Key = $this->mapToGa4->invoke($this->controller, $canonicalKey);
            $roundTripKey = $this->mapFromGa4->invoke($this->controller, $ga4Key);
            
            expect($roundTripKey)->toBe($canonicalKey);
            $testedKeysCount++;
        }
    }

    expect($testedKeysCount)->toBeGreaterThan(0);
});
