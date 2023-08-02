<?php

namespace Animelhd\AnimesWatching\Traits;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * @property \Illuminate\Database\Eloquent\Collection $watchings
 */
trait Watchinger
{
    public function watching(Model $object): void
    {
        /* @var \Animelhd\AnimesView\Traits\Watchingable|Model $object */
        if (! $this->hasWatchinged($object)) {
            $watching = app(config('animeswatching.watching_model'));
            $watching->{config('animeswatching.user_foreign_key')} = $this->getKey();

            $object->watchings()->save($watching);
        }
    }

    public function unwatching(Model $object): void
    {
        /* @var \Animelhd\AnimesView\Traits\Watchingable $object */
        $relation = $object->watchings()
            ->where('watchingable_id', $object->getKey())
            ->where('watchingable_type', $object->getMorphClass())
            ->where(config('animeswatching.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    public function toggleWatching(Model $object): void
    {
        $this->hasWatchinged($object) ? $this->unwatching($object) : $this->watching($object);
    }

    public function hasWatchinged(Model $object): bool
    {
        return ($this->relationLoaded('watchings') ? $this->watchings : $this->watchings())
            ->where('watchingable_id', $object->getKey())
            ->where('watchingable_type', $object->getMorphClass())
            ->count() > 0;
    }

    public function watchings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('animeswatching.watching_model'), config('animeswatching.user_foreign_key'), $this->getKeyName());
    }

    public function attachWatchingStatus(&$watchingables, callable $resolver = null)
    {
        $watchings = $this->watchings()->get()->keyBy(function ($item) {
            return \sprintf('%s-%s', $item->watchingable_type, $item->watchingable_id);
        });

        $attachStatus = function ($watchingable) use ($watchings, $resolver) {
            $resolver = $resolver ?? fn ($m) => $m;
            $watchingable = $resolver($watchingable);

            if (\in_array(Watchingable::class, \class_uses($watchingable))) {
                $key = \sprintf('%s-%s', $watchingable->getMorphClass(), $watchingable->getKey());
                $watchingable->setAttribute('has_watchinged', $watchings->has($key));
            }

            return $watchingable;
        };

        switch (true) {
            case $watchingables instanceof Model:
                return $attachStatus($watchingables);
            case $watchingables instanceof Collection:
                return $watchingables->each($attachStatus);
            case $watchingables instanceof LazyCollection:
                return $watchingables = $watchingables->map($attachStatus);
            case $watchingables instanceof AbstractPaginator:
            case $watchingables instanceof AbstractCursorPaginator:
                return $watchingables->through($attachStatus);
            case $watchingables instanceof Paginator:
                // custom paginator will return a collection
                return collect($watchingables->items())->transform($attachStatus);
            case \is_array($watchingables):
                return \collect($watchingables)->transform($attachStatus);
            default:
                throw new \InvalidArgumentException('Invalid argument type.');
        }
    }

    public function getWatchingItems(string $model)
    {
        return app($model)->whereHas(
            'watchingers',
            function ($q) {
                return $q->where(config('animeswatching.user_foreign_key'), $this->getKey());
            }
        );
    }
}
