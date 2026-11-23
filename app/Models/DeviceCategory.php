<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kode',
        'deskripsi',
        'solusi_cepat',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'solusi_cepat' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function parseSolusiInput(mixed $input): ?array
    {
        if (is_array($input)) {
            $rawLines = $input;
        } elseif (is_string($input)) {
            $rawLines = preg_split('/\r\n|\r|\n/', $input) ?: [];
        } else {
            return null;
        }

        $cleaned = [];
        foreach ($rawLines as $line) {
            $text = trim((string) $line);
            $text = trim((string) preg_replace('/^(?:[-*•]+|\d+[.)])\s+/u', '', $text));
            if ($text !== '') {
                $cleaned[] = $text;
            }
        }

        return empty($cleaned) ? null : array_values($cleaned);
    }
}
