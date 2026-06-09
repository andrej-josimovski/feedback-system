<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QuickSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = now();

        // Clear existing data
        DB::table('teams')->truncate();
        DB::table('users')->truncate();
        DB::table('products')->truncate();
        DB::table('feedback')->truncate();
        DB::table('reports')->truncate();
        DB::table('report_sections')->truncate();

        // Teams
        $teams = DB::table('teams')->insertGetId([
            'name' => 'Product Experience',
            'slug' => 'product-experience',
            'description' => 'Owns onboarding, UX and retention improvements.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admin User
        $adminId = DB::table('users')->insertGetId([
            'name' => 'System Admin',
            'email' => 'admin@feedback.local',
            'team_id' => null,
            'email_verified_at' => $now,
            'password' => Hash::make('password'),
            'role' => 'admin',
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Member User
        $memberId = DB::table('users')->insertGetId([
            'name' => 'John Member',
            'email' => 'john@feedback.local',
            'team_id' => $teams,
            'email_verified_at' => $now,
            'password' => Hash::make('password'),
            'role' => 'member',
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Product
        $productId = DB::table('products')->insertGetId([
            'team_id' => $teams,
            'name' => 'Customer Portal',
            'slug' => 'customer-portal',
            'description' => 'Main web interface where users submit feedback.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Feedback
        DB::table('feedback')->insert([
            'product_id' => $productId,
            'user_id' => $memberId,
            'rating' => 5,
            'comment' => 'Great interface! Very intuitive.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Report
        $reportId = DB::table('reports')->insertGetId([
            'product_id' => $productId,
            'user_id' => $adminId,
            'status' => 'pending_review',
            'title' => 'Customer Portal - Q2 Feedback',
            'period_from' => '2026-04-01',
            'period_to' => '2026-06-30',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Report Section
        DB::table('report_sections')->insert([
            'report_id' => $reportId,
            'theme' => 'Usability',
            'issues' => json_encode(['Interface is clear', 'Easy navigation']),
            'proposals' => json_encode(['Add dark mode', 'Keyboard shortcuts']),
            'ai_summary' => 'Users appreciate the clean interface design.',
            'admin_summary' => null,
            'order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
