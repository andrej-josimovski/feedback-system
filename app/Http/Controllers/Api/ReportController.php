<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // ...existing code...

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
