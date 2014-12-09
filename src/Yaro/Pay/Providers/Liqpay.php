<?php

namespace Yaro\Pay\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
//use Illuminate\Support\Facades\Response;
//use Illuminate\Support\Facades\DB;


class Liqpay
{

    private $response;
    private $rawResponse;

    private $apiVersion   = '1.0.0';
    private $idOrder;
    private $idAcquirer;
    private $idMerchant;
    private $password;
    private $purchaseCurrency;
    private $purchaseCurrencyExponent;
    private $orderDescription;
    private $merchantResponseUrl;
    private $language;
    private $responseUrl;
    private $responseServerUrl;


    public function __construct()
    {
        $this->idAcquirer = Config::get('pay::liqpay.id_acquirer');
        $this->idMerchant = Config::get('pay::liqpay.public_key'); //Config::get('pay::liqpay.id_merchant');
        $this->password = Config::get('pay::liqpay.private_key'); //Config::get('pay::liqpay.password');
        $this->purchaseCurrency = Config::get('pay::liqpay.default_purchase_currency');
        $this->purchaseCurrencyExponent = Config::get('pay::liqpay.default_purchase_currency_exponent');
        $this->orderDescription = Config::get('pay::liqpay.default_order_description');
        $this->language = Config::get('pay::liqpay.language');
        $this->responseUrl = Config::get('pay::liqpay.result_url');
        $this->responseServerUrl = Config::get('pay::liqpay.server_url');
    } // end __construct


    public function getPurchaseCurrencyExponent()
    {
        if (!$this->purchaseCurrencyExponent) {
            throw new \RuntimeException('LiqPay: purchase currency exponent is not set');
        }

        return $this->purchaseCurrencyExponent;
    } // end getPurchaseCurrencyExponent

    public function getOrderDescription()
    {
        if (!$this->orderDescription) {
            throw new \RuntimeException('LiqPay: order description is not set');
        }

        return $this->orderDescription;
    } // end getOrderDescription

    public function getAcquirerID()
    {
        if (!$this->idAcquirer) {
            throw new \RuntimeException('LiqPay: acquirer id is not set');
        }

        return $this->idAcquirer;
    } // end getAcquirerID

    public function getMerchantID()
    {
        if (!$this->idMerchant) {
            throw new \RuntimeException('LiqPay: merchant id is not set');
        }

        return $this->idMerchant;
    } // end getMerchantID

    public function getLanguage()
    {
        if (!$this->language) {
            throw new \RuntimeException('LiqPay: language is not set');
        }

        return $this->language;
    } // end getLanguage

    public function getPassword()
    {
        if (!$this->password) {
            throw new \RuntimeException('LiqPay: password is not set');
        }

        return $this->password;
    } // end getPassword

    public function getOrderID()
    {
        if (!$this->idOrder) {
            throw new \RuntimeException('LiqPay: order id is not passed');
        }

        return $this->idOrder;
    } // end getOrderID

    public function getPurchaseAmount()
    {
        if (!$this->purchaseAmount) {
            throw new \RuntimeException('LiqPay: purchase amount is obsolete');
        }

        return $this->purchaseAmount;
    } // end getPurchaseAmount

    public function getPurchaseCurrency()
    {
        if (!$this->purchaseCurrency) {
            throw new \RuntimeException('LiqPay: purchase currency is not set');
        }

        return $this->purchaseCurrency;
    } // end getPurchaseCurrency

    public function getPhone()
    {
        if (!$this->phone) {
            throw new \RuntimeException('LiqPay: phone is not passed');
        }

        return $this->phone;
    } // end getPhone

    public function getResponseUrl()
    {
        if (!$this->responseUrl) {
            throw new \RuntimeException('LiqPay: response url is not set');
        }

        return URL::to($this->responseUrl);
    } // end getResponseUrl

    public function getResponseServerUrl()
    {
        if (!$this->responseServerUrl) {
            throw new \RuntimeException('LiqPay: response server url is not set');
        }

        return URL::to($this->responseServerUrl);
    } // end getResponseServerUrl

    private function setAmount($amount)
    {
        // Окончательная сумма покупки, 12знаков.Поле
        // дополняется нулями слева до длины 12. Последние
        // 2 знака в суммеозначают копейки. Например
        // 000000001020 = 10грн 20коп
        $this->purchaseAmount = str_pad($amount * 100, 12, '0', STR_PAD_LEFT);
    } // end setAmount



    private function getSign()
    {
        //signature=base64_encode(hexbin(SHA1(password+merid+acqid+orderid+purchaseamt+purchasecurrency+
        //purchaseamt2+purchasecurrency2+cardno+phone+orderdescription+recurringbytoken)))
        $signString = $this->getPassword();
        $signString .= $this->getMerchantID();
        $signString .= $this->getAcquirerID();
        $signString .= $this->getOrderID();
        $signString .= $this->getPurchaseAmount();
        $signString .= $this->getPurchaseCurrency();
        //$signString .= $this->getPhone();
        $signString .= $this->getOrderDescription();

        return base64_encode(hex2bin(sha1($signString)));
    } // end getSign

    private function doCheckCreateParams($params)
    {
        $required = array('id_order', 'amount');
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \RuntimeException('LiqPay: create param is not set - '. $field);
            }
        }
    } // end doCheckCreateParams

    public function create($params)
    {
        $this->doCheckCreateParams($params);

        $this->idOrder = $params['id_order'];
        $this->setAmount($params['amount']);
        //$this->phone = $phone;

        if (isset($params['desc'])) {
            $this->orderDescription = $params['desc'];
        }

        if (isset($params['currency'])) {
            $this->purchaseCurrency = $params['currency'];
        }

        return $this;
    } // end create

    public function getCheckoutParams()
    {
        $params = array(
            'action' => 'https://ecommerce.liqpay.com/ecommerce/checkout',
            'input'  => array(),
        );

        $params['input']['version'] = $this->apiVersion;
        $params['input']['acqid'] = $this->getAcquirerID();
        $params['input']['merid'] = $this->getMerchantID();
        $params['input']['orderid'] = $this->getOrderID();
        $params['input']['purchaseamt'] = $this->getPurchaseAmount();
        $params['input']['purchasecurrency'] = $this->getPurchaseCurrency();
        $params['input']['purchasecurrencyexponent'] = $this->getPurchaseCurrencyExponent();
        $params['input']['orderdescription'] = $this->getOrderDescription();
        $params['input']['signature'] = $this->getSign();

        $params['input']['lang'] = $this->getLanguage();

        $params['input']['merrespurl'] = $this->getResponseUrl();
        $params['input']['merrespurl2'] = $this->getResponseServerUrl();

        return $params;
    } // end getCheckoutParams

    public function getPaymentID()
    {
        return $this->response['pid'];
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

