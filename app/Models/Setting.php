<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /** Defaults from the proposal (section 5: 20-30 % online, more prudent with fresh produce). */
    public const DEFAULTS = [
        'online_share' => '25',
        'online_share_fresh' => '20',
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key): ?string
    {
        return Cache::rememberForever("setting.{$key}", fn () => static::find($key)?->value ?? self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }
}
