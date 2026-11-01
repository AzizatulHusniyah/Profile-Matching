<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KhsDocument extends Model
{
    protected $fillable = [
        'user_id',
        'semester',
        'student_name',
        'original_filename',
        'file_path',
        'file_hash',
        'status',
        'error_message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
