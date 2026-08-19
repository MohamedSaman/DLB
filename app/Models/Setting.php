<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'status',
        'description',
        'date',
    ];

    
    protected $casts = [
        'date' => 'datetime',
    ];

    public static function getVal($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting !== null ? $setting->value : $default;
    }

    public static function setVal($key, $value, $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string)$value,
                'description' => $description,
                'date' => now()
            ]
        );
    }
}
