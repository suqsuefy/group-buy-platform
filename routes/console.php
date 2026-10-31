<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about', function () {
    $this->info('Group Buying API');
})->purpose('Display application information');
