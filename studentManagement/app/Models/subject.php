<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'id',
        'name',
        'lecturerID'
    ];

    protected $casts = ['id' => 'string'];


    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturerID', 'id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'subjectID', 'id');
    }

    
}

