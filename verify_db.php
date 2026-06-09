#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$connection = DB::connection();

try {
    $users = DB::table('users')->count();
    $reports = DB::table('reports')->count();
    $feedback = DB::table('feedback')->count();

    echo "✅ PostgreSQL Connection Successful!\n\n";
    echo " Database Stats:\n";
    echo "   Users: $users\n";
    echo "   Reports: $reports\n";
    echo "   Feedback: $feedback\n";

    $admin = DB::table('users')->where('role', 'admin')->first();
    echo "\n Admin User:\n";
    echo "   Email: {$admin->email}\n";
    echo "   Password: password\n";

    $member = DB::table('users')->where('role', 'member')->first();
    echo "\n Member User:\n";
    echo "   Email: {$member->email}\n";
    echo "   Password: password\n";

} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
