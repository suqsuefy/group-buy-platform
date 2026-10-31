<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about', function () {
    $this->info('Group Buying API');
})->purpose('Display application information');

Artisan::command('campaigns:expire', function (\App\Services\CampaignService $service) {
    $count = $service->expireOverdueCampaigns();
    $this->info("Expired {$count} overdue campaigns.");
})->purpose('Expire overdue campaigns and release unpurchased reservations');
