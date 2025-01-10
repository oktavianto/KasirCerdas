<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Barang extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'foto_url',
    ]; 

    public function getFotoUrlAttribute()
    {
        try {
            return Storage::disk('r2')->url($this->foto);
        } catch (\Throwable $th) {
            return 'https://via.placeholder.com/150';
        }
    }

    public function bisnis()
    {
        return $this->belongsTo(Bisnis::class);
    }
    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
