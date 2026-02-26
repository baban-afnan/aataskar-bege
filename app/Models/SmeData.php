<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmeData extends Model
{
    use HasFactory;

    protected $table = 'sme_datas';

    protected $fillable = [
        'data_id',
        'network',
        'plan_type',
        'amount',
        'size',
        'validity',
        'status',
    ];

    /**
     * Calculate final price for a specific user role.
     */
    public function calculatePriceForRole($role)
    {
        $service = Services1::where('name', 'SME Data')->first();
        if (!$service) return (float)$this->amount;

        $networkMap = [
            'MTN' => 'SME01',
            'AIRTEL' => 'SME02',
            'GLO' => 'SME03',
            '9MOBILE' => 'SME04'
        ];

        $fieldCode = $networkMap[strtoupper($this->network)] ?? null;
        if (!$fieldCode) return (float)$this->amount;

        $field = $service->fields()->where('field_code', $fieldCode)->first();
        if (!$field) return (float)$this->amount;
        
        $markup = $field->getPriceForUserType($role);

        return (float)$this->amount + (float)$markup;
    }
}
