<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'medical_record_number',
        'name',
        'birth_date',
        'address',
        'phone'
    ];

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }
}
