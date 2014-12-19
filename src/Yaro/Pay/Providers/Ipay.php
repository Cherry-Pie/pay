<?php

namespace Yaro\Pay\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
//use Illuminate\Support\Facades\Response;
//use Illuminate\Support\Facades\DB;


class Ipay
{

    private $response;
    private $rawResponse;

    private $idMerchant;
    private $idService = null;
    private $merchantKey;
    private $systemKey;
    private $failUrl;
    private $successUrl;
    private $transactions = array();
    private $apiVersion   = '3.00';
    private $currency;
    private $language;
    private $lifetime;
    private $sandboxServerUrl = 'https://api.sandbox.ipay.ua/';
    private $serverUrl        = 'https://api.ipay.ua/';


    public function __construct()
    {
        $this->idMerchant  = Config::get('pay::ipay.id_merchant');
        $this->idService   = Config::get('pay::ipay.id_service');
        $this->merchantKey = Config::get('pay::ipay.merchant_key');
        $this->systemKey   = Config::get('pay::ipay.system_key');

        $this->failUrl    = Config::get('pay::ipay.url_fail');
        $this->successUrl = Config::get('pay::ipay.url_success');

        $this->currency = Config::get('pay::ipay.currency');
        $this->language = Config::get('pay::ipay.language');
        $this->lifetime = Config::get('pay::ipay.lifetime');

        //$this->apiVersion = Config::get('pay::ipay.api_version');
    } // end __construct

    public function getLanguage()
    {
        if (!$this->language) {
            throw new \RuntimeException('iPay: language is not set');
        }

        return $this->language;
    } // end getLanguage
    
    public function getTerminalID()
    {
        $idTerminal = Config::get('pay::ipay.id_terminal');
        return $idTerminal ? : false;
    } // end getTerminalID

    public function getLifetime()
    {
        if (!$this->lifetime) {
            throw new \RuntimeException('iPay: lifetime is not set');
        }

        return $this->lifetime;
    } // end getLifetime

    public function getMerchantID()
    {
        if (!$this->idMerchant) {
            throw new \RuntimeException('iPay: merchant id is not set');
        }

        return $this->idMerchant;
    } // end getMerchantID

    public function getServiceID()
    {
        if (is_null($this->idService)) {
            throw new \RuntimeException('iPay: service id is not set');
        }

        return $this->idService;
    } // end getServiceID

    public function getMerchantKey()
    {
        if (!$this->merchantKey) {
            throw new \RuntimeException('iPay: merchant key is not set');
        }

        return $this->merchantKey;
    } // end getMerchantKey

    public function getSystemKey()
    {
        if (!$this->systemKey) {
            throw new \RuntimeException('iPay: system key is not set');
        }

        return $this->systemKey;
    } // end getSystemKey

    private function getAuthSaltAndSign()
    {
        $salt = sha1(microtime(true));
        $sign = hash_hmac('sha512', $salt, $this->getMerchantKey());

        return array($salt, $sign);
    } // end getAuthSaltAndSign

    private function getSuccessUrl()
    {
        if (!$this->successUrl) {
            throw new \RuntimeException('iPay: success url is not set');
        }

        return URL::to($this->successUrl);
    } // end getSuccessUrl

    private function getFailUrl()
    {
        if (!$this->failUrl) {
            throw new \RuntimeException('iPay: fail url is not set');
        }

        return URL::to($this->failUrl);
    } // end getFailUrl

    private function getCurrency()
    {
        if (!$this->currency) {
            throw new \RuntimeException('iPay: currency is not set');
        }

        return $this->currency;
    } // end getCurrency

    private function getCreateRequestParams()
    {
        $params = array();

        $params['auth']['mch_id'] = $this->getMerchantID();

        list($salt, $sign) = $this->getAuthSaltAndSign();
        $params['auth']['salt'] = $salt;
        $params['auth']['sign'] = $sign;

        $params['urls']['good'] = $this->getSuccessUrl();
        $params['urls']['bad']  = $this->getFailUrl();

        $params['transactions'] = $this->transactions;

        $params['lifetime'] = $this->getLifetime();
        $params['version']  = $this->apiVersion;
        $params['lang']     = $this->getLanguage();

        return $params;
    } // end getCreateRequestParams

    private function getCompleteRequestParams($idPayment)
    {
        $params = array();

        $params['auth']['mch_id'] = $this->getMerchantID();

        list($salt, $sign) = $this->getAuthSaltAndSign();
        $params['auth']['salt'] = $salt;
        $params['auth']['sign'] = $sign;

        $params['version']  = $this->apiVersion;

        $params['pid']    = $idPayment;
        $params['action'] = 'complete';

        return $params;
    } // end getCompleteRequestParams

    private function getReverseRequestParams($idPayment)
    {
        $params = array();

        $params['auth']['mch_id'] = $this->getMerchantID();

        list($salt, $sign) = $this->getAuthSaltAndSign();
        $params['auth']['salt'] = $salt;
        $params['auth']['sign'] = $sign;

        $params['version']  = $this->apiVersion;

        $params['pid']    = $idPayment;
        $params['action'] = 'reversal';

        return $params;
    } // end getReverseRequestParams

    public function create(array $transactions)
    {
        $this->doPrepareTransactions($transactions);

        $params = $this->getCreateRequestParams();
        $xmlData = $this->paramsToXml($params);

        $response = $this->doCurlRequest($xmlData);

        $this->doCheckResponseSign($response);

        $this->rawResponse = $response;
        $this->response = $this->doConvertXmlToArray($response);

        return $this;
    } // end create
    
    public function check()
    {
        $response = \Input::get('xml');
        if (!$response) {
            throw new \RuntimeException('iPay: there is no xml passed - '. json_encode(\Input::all()));
        }
        
        $this->doCheckResponseSign($response);

        $this->rawResponse = $response;
        $this->response = $this->doConvertXmlToArray($response);

        return $this;
    } // end check

    public function complete($idPayment)
    {
        $params = $this->getCompleteRequestParams($idPayment);
        $xmlData = $this->paramsToXml($params);

        $response = $this->doCurlRequest($xmlData);
        $this->doCheckResponseSign($response);

        $this->rawResponse = $response;
        $this->response = $this->doConvertXmlToArray($response);

        return $this;
    } // end complete
    
    public function isOk()
    {
        return isset($this->response['status']) && $this->response['status'] == 5;
    } // end isOk
    
    public function getStatusMessage()
    {
        switch ($this->response['status']) {
            // Регистрация платежа:
            case '1':
                return 'Платеж успешно зарегистрирован';
            case '2':
                return 'Ошибка при регистрации платежа';
            // Авторизация средств на карте:
            case '3':
                return 'Авторизация средств на карте успешна';
            case '4':
                return 'Ошибка при авторизации средств на карте';
            // Списаниесредств скарты:
            case '5':
                return 'Списание средств с карты успешно';
            case '6':
                return 'Ошибка при списании средств с карты';
            // Запрос на отложенное списание:
            case '7':
                return 'Запрос на списание обработан успешно';
            case '8':
                return 'Ошибка при выполнении запроса на списание';
            // Запрос на отложенную отмену:
            case '9':
                return 'Запрос на отмену авторизации выполнен успешно';
            case '10':
                return 'Ошибка при выполнении запроса на отмену';
            default:
                throw new \RuntimeException('iPay: not implemented status code - '. $this->response['status']);
        }
    } // end getStatusMessage

    public function reverse($idPayment)
    {
        $params = $this->getReverseRequestParams($idPayment);
        $xmlData = $this->paramsToXml($params);

        $response = $this->doCurlRequest($xmlData);
        $this->doCheckResponseSign($response);

        $this->rawResponse = $response;
        $this->response = $this->doConvertXmlToArray($response);

        return $this;
    } // end complete

    private function doConvertXmlToArray($response)
    {
        $xml = simplexml_load_string($response);

        return json_decode(json_encode((array) $xml), true);
    } // end doConvertXmlToArray

    private function doCheckResponseSign($response)
    {
        $response = $this->doConvertXmlToArray($response);

        $salt = $response['salt'];
        $sign = $response['sign'];

        /*
        preg_match('|\<salt\>(.*?)\<\/salt\>|ism', $response, $matches);
        $salt = $matches[1];

        preg_match('|\<sign\>(.*?)\<\/sign\>|ism', $response, $matches);
        $sign = $matches[1];
        */
        if (!$this->doCheckSign($salt, $sign)) {
            throw new \RuntimeException('iPay: system sign is incorrect');
        }
    } // end doCheckResponseSign

    private function doCheckSign($salt, $sign)
    {
        return (hash_hmac('sha512', $salt, $this->getSystemKey()) == $sign);
    } // end doCheckSign

    private function doCurlRequest($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='. $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_URL, $this->getServiceUrl());

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    } // end doCurlRequest

    private function getServiceUrl()
    {
        if (Config::get('pay::is_sandbox')) {
            return $this->sandboxServerUrl;
        }

        return $this->serverUrl;
    } // end getServiceUrl

    public function paramsToXml($data, $rootNodeName = 'payment', $xml = null)
    {
        if (is_null($xml)) {
            $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><'. $rootNodeName .' />');
        }

        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = "transaction";
            }

            if (is_array($value)) {
                $node = $xml->addChild($key);
                $this->paramsToXml($value, $rootNodeName, $node);
            } else {
                $value = trim($value);
                $xml->addChild($key, $value);
            }
        }

        return $xml->asXML();
    } // end paramsToXml

    private function getTransactionAttribute($transaction, $ident, $default = null)
    {
        if (!isset($transaction[$ident])) {
            if (!is_null($default)) {
                return $default;
            }
            throw new \RuntimeException('iPay: transaction key is required - '. $ident);
        }

        return $transaction[$ident];
    } // end getTransactionAttribute

    private function hasTransactionAttribute($transaction, $ident)
    {
        return isset($transaction[$ident]);
    } // end hasTransactionAttribute

    private function doPrepareTransactions($transactions)
    {
        $prepared = array();

        reset($transactions);

        if (is_array(current($transactions))) {
            foreach ($transactions as $transaction) {
                $prepared[] = $this->onTransaction($transaction);
            }
        } else {
            $prepared[] = $this->onTransaction($transactions);
        }

        if (!$prepared) {
            throw new \RuntimeException('iPay: no transactions where passed');
        }

        $this->transactions = $prepared;
    } // end doPrepareTransactions

    private function onTransaction($transaction)
    {
        $tr = array();
        $tr['mch_id'] = $this->getMerchantID();
        $tr['srv_id'] = $this->getServiceID();
        if ($this->getTerminalID()) {
            $tr['terminal'] = $this->getTerminalID();
        }
        // тип транзакции. [ 10 (авторизация) или 11(списание)]
        $tr['type']   = 11;
        $tr['amount'] = $this->getTransactionAttribute($transaction, 'amount') * 100;
        $tr['currency'] = $this->getCurrency();
        
        $desc = $this->getTransactionAttribute($transaction, 'desc');
        $tr['desc'] = mb_strcut($desc, 0, 248);

        if ($this->hasTransactionAttribute($transaction, 'fee')) {
            $tr['fee'] = $transaction['fee'];
        }
        if ($this->hasTransactionAttribute($transaction, 'note')) {
            $tr['note'] = $transaction['note'];
        }

        $info = $this->getTransactionAttribute($transaction, 'info', array());
        $tr['info'] = json_encode($info);

        return $tr;
    } // end onTransaction
    
    public function getPaymentID()
    {
        return isset($this->response['pid']) ? $this->response['pid'] : $this->response['@attributes']['id'];
    } // end getPaymentID

    public function getRedirectUrl()
    {
        return $this->response['url'];
    } // end getRedirectUrl

    public function getResponse()
    {
        return $this->response;
    } // end getResponse

    public function getRawResponse()
    {
        return $this->rawResponse;
    } // end getRawResponse

}

