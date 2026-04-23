<?php

namespace App\Observers;

use App\Models\Report;

class ReportObserver
{
    public function creating(Report $report): void
    {
        if (empty($report->status)) {
            $report->status = 'pending_review';
        }
    }
}

