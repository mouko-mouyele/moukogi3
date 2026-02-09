<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'title',
        'message',
        'status',
        'email_sent',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'email_sent' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
