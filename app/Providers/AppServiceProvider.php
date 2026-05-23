<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\Feedback;
use App\Observers\ReportObserver;
use App\Policies\ReportPolicy;
use App\Policies\FeedbackPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Report::class => ReportPolicy::class,
        Feedback::class => FeedbackPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Report::observe(ReportObserver::class);
    }
}
