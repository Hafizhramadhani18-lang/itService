<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_aset',
        'lokasi_ruangan',
        'kode_perangkat',
        'frekuensi',
        'tim',
        'teknisi_id',
        'teknisi_nama',
        'kegiatan',
        'checklist',
        'catatan_teknisi',
        'kirim_pengingat',
        'tanggal_berikutnya',
        'jam_mulai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_berikutnya' => 'date',
            'checklist' => 'array',
            'kirim_pengingat' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }
}
