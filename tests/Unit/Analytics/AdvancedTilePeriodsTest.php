<?php

use App\Services\Analytics\AdvancedTilePeriods;

it('computes inclusive current and previous periods across leap day', function () {
    $periods = (new AdvancedTilePeriods)->resolve('2024-01-01', '2024-02-29', 'last_7_days');
    expect($periods)->toBe([
        'current' => ['start' => '2024-02-23', 'end' => '2024-02-29'],
        'previous' => ['start' => '2024-02-16', 'end' => '2024-02-22'],
    ]);
});

it('rejects impossible or reversed date ranges', function ($start, $end) {
    (new AdvancedTilePeriods)->resolve($start, $end);
})->with([['2024-02-30', '2024-03-01'], ['2024-03-02', '2024-03-01']])->throws(InvalidArgumentException::class);

it('reduces numeric series values without treating missing points as zero', function () {
    $periods = new AdvancedTilePeriods;
    expect($periods->reduce([1, null, 3], 'latest'))->toBe(3.0)
        ->and($periods->reduce([1, null, 3], 'sum'))->toBe(4.0)
        ->and($periods->reduce([1, null, 3], 'average'))->toBe(2.0)
        ->and($periods->reduce([null], 'latest'))->toBeNull();
});
