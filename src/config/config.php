<?php

return array(

    'is_sandbox' => true,

    // link for notifications is setted by iPay support team
    'ipay' => array(
        'id_merchant'  => 417,
        'id_service'   => 0,
        'merchant_key' => '78201f17d31766ea8546f16b8f4bbec4af240e5f',
        'system_key'   => '301e1a52f718c302656ebf761ba5905db1e56f6f',
        'url_success'  => '/good',
        'url_fail'     => '/fail',
        // ru | ua | en
        'language' => 'ru',
        // in hours
        'lifetime' => 1,
        //
        'currency' => 'UAH',
    ),

    'liqpay' => array(
        'private_key' => '7WQheQx3FonCvl5sgTIVXJMY06I0VRK0xqnx8xpO',
        'public_key'  => 'i51083393969',

        // 414963 – UA | 469584 – RU
        'id_acquirer' => '414963',
        // URL, на который система должна перенаправлять
        // клиента с результатом платежа. В случае отсутствия
        // параметра переадресация происходит на страницу
        // результата оплаты классического чекаута
        // client redirect via post
        'result_url' => '/liqpay/response',
        // URL, на который система должна отправлять ответ с
        // результатом платежа напрямую, параллельно
        // отправке через браузер клиента.
        // Этот URL является дублирующим каналом доставки
        // ответа от банка.
        // server call via post
        'server_url' => '/liqpay/server/response',
        // 980 – Украинская гривна
        // 840 – Доллар США
        // 643 – Российский рубль
        // в соответствии с ISO
        'default_purchase_currency' => '980',
        // экспонента суммы покупки (количествознаков,
        // выделяемое под дробную часть)
        'default_purchase_currency_exponent' => '2',

        // Описание покупки вкодировке UTF-8
        'default_order_description' => '',

        // Язык чекаута
        // rus- русский
        // ukr- украинский
        // eng- английский
        // lav - латышский
        // ita- итальянский
        // geo- грузинский
        'language' => 'rus',

    ),

);
