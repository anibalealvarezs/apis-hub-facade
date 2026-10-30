<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo \Illuminate\Support\Facades\Blade::render('<x-entity-attribute-filters tab="activeTab" />');
