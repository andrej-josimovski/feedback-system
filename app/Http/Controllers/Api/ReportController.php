<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::query()
            ->with(['product', 'user', 'publisher'])
            ->latest('id')
            ->get();

        return response()->json($reports);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $this->authorize('create', Report::class);

        $validated = $request->validated();

        $report = Report::create([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'] ?? $request->user()->id,
            'title' => $validated['title'],
            'period_from' => $validated['period_from'],
            'period_to' => $validated['period_to'],
        ]);

        return response()->json($report->load(['product', 'user']), 201);
    }

    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json($report->load(['product', 'user', 'publisher', 'sections']));
    }

    public function sections(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json($report->sections()->orderBy('order')->get());
    }

    public function update(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'period_from' => 'sometimes|date',
            'period_to' => 'sometimes|date|after:period_from',
        ]);

        $report->update($validated);

        return response()->json($report);
    }

    public function publish(Request $request, Report $report): JsonResponse
    {
        $this->authorize('publish', $report);

        if ($report->status !== 'pending_review') {
            return response()->json([
                'message' => 'Only reports in pending_review status can be published.',
                'current_status' => $report->status,
            ], 422);
        }

        $report->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Report published successfully.',
            'report' => $report,
        ]);
    }
}
