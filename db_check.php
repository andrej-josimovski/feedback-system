#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$container = $app->make(\Illuminate\Contracts\Container\Container::class);

use App\Models\User;
use App\Models\Team;
use App\Models\Product;
use App\Models\Feedback;
use App\Models\Report;

echo "=== DATABASE CHECK ===\n\n";

echo "Teams: " . Team::count() . "\n";
echo "Users: " . User::count() . "\n";
echo "Products: " . Product::count() . "\n";
echo "Feedback: " . Feedback::count() . "\n";
echo "Reports: " . Report::count() . "\n";

echo "\n=== USERS ===\n";
$users = User::select('id', 'name', 'email', 'role')->get();
foreach ($users as $user) {
    echo "{$user->id}: {$user->name} ({$user->email}) - [{$user->role}]\n";
}

echo "\n=== REPORTS ===\n";
$reports = Report::select('id', 'title', 'status')->get();
foreach ($reports as $report) {
    echo "{$report->id}: {$report->title} - Status: {$report->status}\n";
}

echo "\n✅ Database is ready!\n";


