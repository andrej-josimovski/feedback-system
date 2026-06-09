<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        // Teams
        $teams = [
            'Product Experience' => [
                'slug' => 'product-experience',
                'description' => 'Owns onboarding, UX and retention improvements.',
            ],
            'Platform Reliability' => [
                'slug' => 'platform-reliability',
                'description' => 'Owns performance, uptime and integrations.',
            ],
            'Growth and Billing' => [
                'slug' => 'growth-billing',
                'description' => 'Owns plans, pricing and conversion.',
            ],
        ];

        $teamIds = [];
        foreach ($teams as $name => $teamData) {
            $teamIds[$name] = DB::table('teams')->insertGetId([
                'name' => $name,
                'slug' => $teamData['slug'],
                'description' => $teamData['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Users
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

        $teamUsers = [];
        foreach ($teamIds as $teamName => $teamId) {
            for ($i = 1; $i <= 3; $i++) {
                $teamUsers[$teamName][] = DB::table('users')->insertGetId([
                    'name' => $teamName . ' User ' . $i,
                    'email' => Str::slug($teamName, '.') . '.user' . $i . '@feedback.local',
                    'team_id' => $teamId,
                    'email_verified_at' => $now,
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'remember_token' => Str::random(10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Products
        $products = [
            [
                'team' => 'Product Experience',
                'name' => 'Customer Portal',
                'slug' => 'customer-portal',
                'description' => 'Main web interface where users submit and track requests.',
            ],
            [
                'team' => 'Product Experience',
                'name' => 'Mobile App',
                'slug' => 'mobile-app',
                'description' => 'iOS and Android app for quick actions and notifications.',
            ],
            [
                'team' => 'Platform Reliability',
                'name' => 'Public API',
                'slug' => 'public-api',
                'description' => 'External API used by partners and internal tooling.',
            ],
            [
                'team' => 'Growth and Billing',
                'name' => 'Subscriptions',
                'slug' => 'subscriptions',
                'description' => 'Plan management, invoicing and payment flows.',
            ],
        ];

        $productIds = [];
        foreach ($products as $product) {
            $productIds[$product['name']] = DB::table('products')->insertGetId([
                'team_id' => $teamIds[$product['team']],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'description' => $product['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Feedback entries (ratings + text comments used later for AI grouping).
        $feedbackSamples = [
            'Customer Portal' => [
                [5, 'The new dashboard is much clearer.'],
                [2, 'Filters reset after refresh and I lose context.'],
                [3, 'Search is useful, but loads slowly with many records.'],
                [4, 'Export works well, but CSV headers are inconsistent.'],
            ],
            'Mobile App' => [
                [2, 'Push notifications arrive late.'],
                [4, 'Navigation is cleaner after last release.'],
                [1, 'App crashes when uploading a photo attachment.'],
                [3, 'Offline mode helps, but sync conflicts are confusing.'],
            ],
            'Public API' => [
                [5, 'Webhook reliability improved a lot this month.'],
                [2, 'Rate limit errors are hard to debug.'],
                [3, 'Docs are good, but code samples are outdated.'],
                [4, 'Auth flow is secure and easy to integrate.'],
            ],
            'Subscriptions' => [
                [2, 'Invoice PDF formatting breaks for long company names.'],
                [3, 'Trial extension flow is not obvious for admins.'],
                [4, 'Checkout is fast and simple.'],
                [1, 'Card update sometimes fails without any error message.'],
            ],
        ];

        foreach ($feedbackSamples as $productName => $entries) {
            $teamName = collect($products)->firstWhere('name', $productName)['team'];
            $possibleUsers = $teamUsers[$teamName];

            foreach ($entries as $index => [$rating, $comment]) {
                DB::table('feedback')->insert([
                    'product_id' => $productIds[$productName],
                    'user_id' => $index % 4 === 0 ? null : $possibleUsers[array_rand($possibleUsers)],
                    'rating' => $rating,
                    'comment' => $comment,
                    'created_at' => now()->subDays(rand(3, 40)),
                    'updated_at' => $now,
                ]);
            }
        }

        // Reports with review workflow states and AI/admin summaries.
        $reportDefinitions = [
            [
                'product' => 'Customer Portal',
                'status' => 'published',
                'title' => 'Customer Portal - Monthly Feedback Report',
                'period_from' => now()->subMonth()->startOfMonth()->toDateString(),
                'period_to' => now()->subMonth()->endOfMonth()->toDateString(),
                'published_by' => $adminId,
                'published_at' => now()->subDays(10),
                'sections' => [
                    [
                        'theme' => 'Usability',
                        'issues' => ['Filter state is lost on refresh', 'Heavy pages feel slow with large datasets'],
                        'proposals' => ['Persist filters in query params', 'Add server-side pagination defaults'],
                        'ai_summary' => 'Most negative sentiment clusters around filter persistence and page speed in high-volume views.',
                        'admin_summary' => 'Priority is filter persistence first, then table-performance optimization in Q2 sprint.',
                    ],
                    [
                        'theme' => 'Reporting',
                        'issues' => ['CSV headers are inconsistent between views'],
                        'proposals' => ['Standardize export schema and add snapshot tests'],
                        'ai_summary' => 'Export complaints are fewer but recurring and point to inconsistent schema naming.',
                        'admin_summary' => 'Agreed. Team will publish a single export contract and validate across modules.',
                    ],
                ],
            ],
            [
                'product' => 'Mobile App',
                'status' => 'pending_review',
                'title' => 'Mobile App - Weekly Signals',
                'period_from' => now()->subDays(14)->toDateString(),
                'period_to' => now()->subDays(1)->toDateString(),
                'published_by' => null,
                'published_at' => null,
                'sections' => [
                    [
                        'theme' => 'Stability',
                        'issues' => ['Crash during photo upload', 'Late push notifications'],
                        'proposals' => ['Collect crash traces by app version', 'Review push queue and retry strategy'],
                        'ai_summary' => 'Comments suggest one high-impact crash flow and a secondary delay issue in notifications.',
                        'admin_summary' => null,
                    ],
                ],
            ],
            [
                'product' => 'Public API',
                'status' => 'draft',
                'title' => 'Public API - Integrator Feedback Draft',
                'period_from' => now()->subDays(30)->toDateString(),
                'period_to' => now()->toDateString(),
                'published_by' => null,
                'published_at' => null,
                'sections' => [
                    [
                        'theme' => 'Developer Experience',
                        'issues' => ['Rate limit errors are unclear', 'Examples in docs are outdated'],
                        'proposals' => ['Return actionable limit headers', 'Refresh SDK snippets and changelog links'],
                        'ai_summary' => 'Partner feedback indicates friction in diagnostics and trust in documentation freshness.',
                        'admin_summary' => null,
                    ],
                ],
            ],
        ];

        foreach ($reportDefinitions as $reportDefinition) {
            $teamName = collect($products)->firstWhere('name', $reportDefinition['product'])['team'];
            $generatorId = $teamUsers[$teamName][array_rand($teamUsers[$teamName])];

            $reportId = DB::table('reports')->insertGetId([
                'product_id' => $productIds[$reportDefinition['product']],
                'user_id' => $generatorId,
                'published_by' => $reportDefinition['published_by'],
                'status' => $reportDefinition['status'],
                'title' => $reportDefinition['title'],
                'period_from' => $reportDefinition['period_from'],
                'period_to' => $reportDefinition['period_to'],
                'published_at' => $reportDefinition['published_at'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($reportDefinition['sections'] as $index => $section) {
                DB::table('report_sections')->insert([
                    'report_id' => $reportId,
                    'theme' => $section['theme'],
                    'issues' => json_encode($section['issues'], JSON_UNESCAPED_UNICODE),
                    'proposals' => json_encode($section['proposals'], JSON_UNESCAPED_UNICODE),
                    'ai_summary' => $section['ai_summary'],
                    'admin_summary' => $section['admin_summary'],
                    'order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
