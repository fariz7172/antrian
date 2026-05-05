<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'poli_id',
        'number',
        'ticket_number',
        'status',
        'patient_id',
        'called_at',
        'finished_at',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    protected $casts = [
        'called_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Get the poli that owns the Queue
     */
    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    /**
     * Scope a query to only include today's queues.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
