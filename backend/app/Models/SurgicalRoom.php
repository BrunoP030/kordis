<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgicalRoom extends Model
{
    protected $fillable = ['nome', 'ativa'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function bookings()
    {
        return $this->hasMany(SurgicalBooking::class);
    }
}
