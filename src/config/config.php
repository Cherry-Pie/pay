<?php

return array(

    // link for notifications is setted by iPay support team
    'ipay' => array(
        'is_sandbox' => true, // sandbox mode
        // берется у саппорта (для каждого мерчанта свой)
        'id_terminal'  => false, // false
        
        'id_merchant'  => 0,
        'id_service'   => 0,
        'merchant_key' => '',
        'system_key'   => '',
        'url_success'  => '/',
        'url_fail'     => '/',
        // ru | ua | en
        'language' => 'ru',
        // in hours
        'lifetime' => 24,
        //
        'currency' => 'UAH',
    ),

    'liqpay' => array(
        'private_key' => '',//'7WQheQx3FonCvl5sgTIVXJMY06I0VRK0xqnx8xpO',
        'public_key'  => '',

        // 414963 – UA | 469584 – RU
        'id_acquirer' => '414963',
        // URL, на который система должна перенаправлять
        // клиента с результатом платежа. В случае отсутствия
        // параметра переадресация происходит на страницу
        // результата оплаты классического чекаута
        // client redirect via post
        'result_url' => '/',
        // URL, на который система должна отправлять ответ с
        // результатом платежа напрямую, параллельно
        // отправке через браузер клиента.
        // Этот URL является дублирующим каналом доставки
        // ответа от банка.
        // server call via post
        'server_url' => '/', // НЕ ИСПОЛЬЗУЕТСЯ?
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
