<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require __DIR__ . '/bootstrap/app.php';
$container = $app->make(\Illuminate\Contracts\Container\Container::class);

echo "Teams: " . DB::table('teams')->count() . "\n";
echo "Users: " . DB::table('users')->count() . "\n";
echo "Feedback: " . DB::table('feedback')->count() . "\n";
echo "Reports: " . DB::table('reports')->count() . "\n";

// Print users
$users = DB::table('users')->select('id', 'name', 'email', 'role')->get();
echo "\n=== Users ===\n";
foreach ($users as $user) {
    echo "{$user->id}: {$user->name} ({$user->email}) - Role: {$user->role}\n";
}

