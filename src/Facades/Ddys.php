<?php

namespace Ddys\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Ddys extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ddys.client';
    }
}

