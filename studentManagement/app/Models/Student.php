<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory;

    protected $hidden = [
        'password',
        'icNumber',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['maskedIC'];

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'icNumber'
    ];

    protected $casts = ['id' => 'string'];


    public function results()
    {
        return $this->hasMany(Result::class, 'studentID', 'id');
    }

    public function getMaskedIcAttribute()
    {
        $ic = $this->icNumber;
        if (!$ic) return null;

        $length = strlen($ic);

        // Safety check: if IC is shorter than 8 chars, show first half
        if ($length <= 8) {
            $visibleLength = floor($length / 2);
            $maskLength = $length - $visibleLength;
            return substr($ic, 0, $visibleLength) . str_repeat('*', $maskLength);
        }

        // Normal case: show first 8 chars, mask the rest
        return substr($ic, 0, 8) . str_repeat('*', $length - 8);
    }
}
