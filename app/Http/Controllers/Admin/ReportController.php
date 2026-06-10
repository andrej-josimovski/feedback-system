<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Jobs\AnalyzeFeedbackJob;
use App\Models\Product;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(): View
    {
        $reports = Report::query()
            ->with(['product:id,name', 'user:id,name'])
            ->latest()
            ->get();

        return view('admin.reports.index', [
            'reports' => $reports,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $report = Report::create([
            'product_id' => $validated['product_id'],
            'user_id' => $request->user()?->id,
            'title' => $validated['title'],
            'period_from' => $validated['period_from'],
            'period_to' => $validated['period_to'],
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', 'Report created.');
    }

    public function show(Report $report): View
    {
        return view('admin.reports.show', [
            'report' => $report->load(['product:id,name', 'user:id,name,email', 'sections']),
        ]);
    }

    public function analyze(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'model' => ['nullable', 'string'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $report->ai_status = 'pending';
        $report->ai_error = null;
        $report->ai_model = $data['model'] ?? $report->ai_model ?? config('services.openrouter.model');
        $report->save();

        AnalyzeFeedbackJob::dispatch($report->id, [
            'model' => $report->ai_model,
            'batch_size' => $data['batch_size'] ?? 25,
        ]);

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', 'AI analysis queued.');
    }

    public function publish(Report $report): RedirectResponse
    {
        if ($report->status !== 'pending_review') {
            $report->status = 'pending_review';
            $report->save();

            return redirect()
                ->route('admin.reports.show', $report)
                ->with('status', 'Report moved to review.');
        }

        $report->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => request()->user()?->id,
        ]);

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', 'Report published.');
    }
}
