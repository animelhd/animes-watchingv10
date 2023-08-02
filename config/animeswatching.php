<?php

return [
    /**
     * Use uuid as primary key.
     */
    'uuids' => false,

    /*
     * User tables foreign key name.
     */
    'user_foreign_key' => 'user_id',

    /*
     * Table name for watchings records.
     */
    'watchings_table' => 'watchings',

    /*
     * Model name for watching record.
     */
    'watching_model' => Animelhd\AnimesWatching\Watching::class,
];