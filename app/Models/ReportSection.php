<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSection extends Model
{
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
