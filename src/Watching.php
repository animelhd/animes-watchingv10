<?php

namespace Animelhd\AnimesWatching;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Animelhd\AnimesWatching\Events\Watchinged;
use Animelhd\AnimesWatching\Events\Unwatchinged;

/**
 * @property \Illuminate\Database\Eloquent\Model $user
 * @property \Illuminate\Database\Eloquent\Model $watchinger
 * @property \Illuminate\Database\Eloquent\Model $watchingable
 */
class Watching extends Model
{
    protected $guarded = [];

    protected $dispatchesEvents = [
        'created' => Watchinged::class,
        'deleted' => Unwatchinged::class,
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = \config('animeswatching.watchings_table');

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        self::saving(function ($watching) {
            $userForeignKey = \config('animeswatching.user_foreign_key');
            $watching->{$userForeignKey} = $watching->{$userForeignKey} ?: auth()->id();

            if (\config('animeswatching.uuids')) {
                $watching->{$watching->getKeyName()} = $watching->{$watching->getKeyName()} ?: (string) Str::orderedUuid();
            }
        });
    }

    public function watchingable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\config('auth.providers.users.model'), \config('animeswatching.user_foreign_key'));
    }

    public function watchinger(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->user();
    }

    public function scopeWithType(Builder $query, string $type): Builder
    {
        return $query->where('watchingable_type', app($type)->getMorphClass());
    }
}
