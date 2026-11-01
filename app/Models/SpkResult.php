<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkResult extends Model
{
    protected $fillable = ['user_id', 'gap_results', 'weighted_gap_results', 'factor_results', 'ranking_results'];
    protected function casts(): array
    {
        return ['gap_results' => 'array', 'weighted_gap_results' => 'array', 'factor_results' => 'array', 'ranking_results' => 'array'];
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
