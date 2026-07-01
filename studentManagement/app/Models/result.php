<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $hidden = [
        'id',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'id',
        'studentID',
        'subjectID',
        'grade',
        'semester'
    ];

    protected $casts = ['id' => 'string'];


    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjectID', 'id');
    }
}
