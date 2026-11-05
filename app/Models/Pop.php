<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Pop extends Model
{
    protected $fillable = ['slug', 'name', 'emoji', 'currency'];

    // URLs look like /p/k3j9x0ab instead of /p/12
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class)->orderBy('id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public static function newSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(8));
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }
}
