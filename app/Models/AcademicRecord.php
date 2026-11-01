<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    protected $fillable = ['user_id', 'khs_document_id', 'semester', 'periode', 'course_name', 'sks', 'grade_letter', 'grade_numeric'];

    public function document(): BelongsTo { return $this->belongsTo(KhsDocument::class, 'khs_document_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
