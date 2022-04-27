<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $with = ['wallet'];

    public function wallet() {
        return $this->hasOne(Wallet::class);
    }

    public function candidates() {
        return $this->belongsToMany(Candidate::class, 'company_candidates')->withTimestamps();
    }
}
