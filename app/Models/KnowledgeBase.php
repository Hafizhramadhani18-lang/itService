<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'judul',
        'kategori',
        'area_penerapan',
        'target_audience',
        'pengantar',
        'langkah_langkah',
        'diagram_kabel',
        'judul_perintah',
        'perintah_cmd',
        'peringatan',
        'tag',
        'perlu_izin_operator',
        'service_id',
        'status',
    ];

    protected $casts = [
        'langkah_langkah' => 'array',
        'diagram_kabel' => 'array',
        'perlu_izin_operator' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function isForEveryone(): bool
    {
        return $this->target_audience === 'all' || $this->target_audience === 'pegawai';
    }

    public function isTeknisiOnly(): bool
    {
        return $this->target_audience === 'teknisi';
    }
}

