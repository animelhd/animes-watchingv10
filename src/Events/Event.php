<?php

namespace Animelhd\AnimesWatching\Events;

use Illuminate\Database\Eloquent\Model;

class Event
{
    public Model $watching;

    public function __construct(Model $watching)
    {
        $this->watching = $watching;
    }
}
