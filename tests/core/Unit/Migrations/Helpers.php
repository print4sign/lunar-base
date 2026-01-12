<?php

if (! function_exists('prefix_table')) {
    /**
     * Get prefixed table name.
     */
    function prefix_table(string $table): string
    {
        return config('lunar.database.table_prefix').$table;
    }
}
