<?php

namespace Animelhd\AnimesWatching\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $watchingers
 * @property \Illuminate\Database\Eloquent\Collection $watchings
 */
trait Watchingable
{
    /**
     * @deprecated renamed to `hasBeenWatchingedBy`, will be removed at 5.0
     */
    public function isWatchingedBy(Model $user)
    {
        return $this->hasBeenWatchingedBy($user);
    }

    public function hasWatchinger(Model $user): bool
    {
        return $this->hasBeenWatchingedBy($user);
    }

    public function hasBeenWatchingedBy(Model $user): bool
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('watchingers')) {
                return $this->watchingers->contains($user);
            }

            return ($this->relationLoaded('watchings') ? $this->watchings : $this->watchings())
                    ->where(\config('animeswatching.user_foreign_key'), $user->getKey())->count() > 0;
        }

        return false;
    }

    public function watchings(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(config('animeswatching.watching_model'), 'watchingable');
    }

    public function watchingers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('animeswatching.watchings_table'),
            'watchingable_id',
            config('animeswatching.user_foreign_key')
        )
            ->where('watchingable_type', $this->getMorphClass());
    }
}
