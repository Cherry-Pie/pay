<?php

namespace Yaro\Pay;

//use Illuminate\Support\Facades\Config;
//use Illuminate\Support\Facades\Response;
//use Illuminate\Support\Facades\DB;


class Pay
{

    public function ipay()
    {
        $entity = new Providers\Ipay();

        return $entity;
    } // end ipay

    public function liqpay()
    {
        $entity = new Providers\Liqpay();

        return $entity;
    } // end liqpay

}

