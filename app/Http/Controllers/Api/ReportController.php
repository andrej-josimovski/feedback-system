<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function index(): JsonResponse
    {
        $reports = Report::query()
            ->with([
                'product:id,name,slug,team_id',
                'product.team:id,name,slug',
                'publisher:id,name,email',
            ])
            ->withCount('sections')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reports);
    }

    public function show(Report $report): JsonResponse
    {
        $report->load([
            'product:id,name,slug,team_id',
            'product.team:id,name,slug',
            'user:id,name,email',
            'publisher:id,name,email',
            'sections' => fn ($query) => $query->orderBy('order'),
        ]);

        return response()->json($report);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = new Report();
        $report->product_id = (int) $request->validated('product_id');
        $report->user_id = $request->validated('user_id');
        $report->title = $request->validated('title');
        $report->period_from = $request->validated('period_from');
        $report->period_to = $request->validated('period_to');
        // status is set by ReportObserver to 'pending_review'
        $report->save();

        $report->load([
            'product:id,name,slug',
            'user:id,name,email',
        ]);

        return response()->json($report, 201);
    }

    public function sections(Report $report): JsonResponse
    {
        $sections = $report->sections()->orderBy('order')->get();

        return response()->json([
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'status' => $report->status,
            ],
            'sections' => $sections,
        ]);
    }
}

