<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->setLocale('en');
$explorer = file_get_contents(__DIR__.'/../../resources/views/filament/app/pages/google-search-console-dashboard.blade.php');
$widget = file_get_contents(__DIR__.'/../../resources/views/filament/app/pages/dashboard-builder.blade.php');
preg_match('/<label x-show="searchTypesEnabled".*?<\/label>/s', $explorer, $explorerMatch);
preg_match('/<template x-if="isGscSearchTypeFilter\(flt\)">.*?<\/template>/s', $widget, $widgetMatch);
if (!$explorerMatch || !$widgetMatch) throw new RuntimeException('Search type controls not found');
echo json_encode([
    'explorer' => \Illuminate\Support\Facades\Blade::render($explorerMatch[0]),
    'widget' => \Illuminate\Support\Facades\Blade::render($widgetMatch[0]),
    'catalog' => \App\Services\Analytics\GscAnalyticsCatalog::tree(),
    'options' => \App\Services\Analytics\GscAnalyticsCatalog::searchTypes(),
], JSON_THROW_ON_ERROR);
