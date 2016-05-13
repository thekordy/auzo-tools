<?php

namespace Kordy\AuzoTools\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kordy\AuzoTools\Services\GenerateAbilities
 */
class GenerateAbilities extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'GenerateAbilities';
    }
}