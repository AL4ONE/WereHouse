<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_id',
        'created_by',
        'approved_by',
        'status',
        'order_date',
        'expected_date',
        'notes',
        'total_amount',
        'is_training',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_training' => 'boolean',
    ];

    public function scopeTrainingMode($query, $isTraining)
    {
        return $query->where('is_training', $isTraining);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Recalculate total_amount from items
     */
    public function recalculateTotal()
    {
        $this->update([
            'total_amount' => $this->items()->sum('subtotal'),
        ]);
    }
}
