<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    public function contactedBy() {
        return $this->belongsToMany(Company::class, 'company_candidates')->withTimestamps();
    }

    public function hiredBy()
    {
        return $this->belongsTo(Company::class, 'hired_by');
    }
}
