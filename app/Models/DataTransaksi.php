<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTransaksi extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];
    public function bisnis()
    {
        return $this->belongsTo(Bisnis::class);
    }
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabangs_id');
    }
    public function metodePembayaran() {
        return $this->belongsTo(MetodePembayaran::class, 'nama_mp');
    }
}
