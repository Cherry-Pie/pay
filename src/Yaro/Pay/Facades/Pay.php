<?php

namespace Yaro\Pay\Facades;

use Illuminate\Support\Facades\Facade;


class Pay extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'yaro_pay';
    } // end getFacadeAccessor

}