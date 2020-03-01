<?php

namespace Pichicacax\Asiapay;

use Illuminate\Support\Arr;

class Pay
{
    protected $url = '';
    protected $hashSecret = '';

    protected $currency = [
        '608' => 'PHP',
        '840' => 'USD',
        '344' => 'HKD',
        '702' => 'SGD',
        '156' => 'CNY',
        '392' => 'JPY',
        '901' => 'TWD',
        '036' => 'AUD',
        '978' => 'EUR',
        '826' => 'GBP',
        '124' => 'CAD',
        '446' => 'MOP',
        '764' => 'THB',
        '458' => 'MYR',
        '360' => 'IDR',
        '410' => 'KRW',
    ];

    protected $params = [
         // System based
        'merchantId' => '',                 // Merchant id
        'orderRef' => '',                   // Merchant‘s Order Reference Number
        
        'lang' => 'E',                      // Language, defaults to English

        'mpsMode' => 'NIL',                 // Multi-Currency Processing Service (MPS) Mode
        'payType' => 'N',                   // Payment type. N - normal payment(sales), H - hold payment (authorize only)
        'payMethod' => 'ALL',               // Payment method, defaults to ALL enrolled payments

        // Amounts and currencies
        'currCode' => '608',                // Currency, defaults to PHP
        'amount' => '',                     // Amount to be paid
        
        // Installments
        'installment_service' => 'F',       // Installment, T - true, F - false,
        'installment_period' => 0,          // In number of months for the installment

        // Others
        'remark' => '',                     // Optional text field. Will not be displayed in transaction page

        // Redirections
        'cancelUrl' => '',
        'failUrl' => '',
        'successUrl' => '',
    ];

    /**
     * Set Asiapay html variables
     *
     * @param  array $params HTML variables to set
     * @return void
     */
    public function setParams($params = [])
    {
        foreach ($params as $key => $value) {
            if (isset($this->params[$key])) {
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Set action url
     * 
     * @param  string $url 
     * @return void
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * Set hash secret
     * 
     * @param  string $secret 
     * @return void
     */
    public function setHashSecret(string $secret)
    {
        $this->hashSecret = $secret;
    }

    /**
     * Set merchant id
     * 
     * @param  string $id 
     * @return void
     */
    public function setMerchantID(string $id)
    {
        Arr::set($this->params, 'merchantId', $id);
    }

    /**
     * Set order reference
     * 
     * @param  string $ref 
     * @return void
     */
    public function setOrderRef(string $ref)
    {
        Arr::set($this->params, 'orderRef', $ref);
    }

    /**
     * Set currency code.
     * Accepts "PHP", "USD", etc.
     * 
     * @param  string $currency 
     * @return void
     */
    public function setCurrency(string $currency)
    {
        if (! is_numeric($currency)) {
            $currency = array_search($currency, $this->currency);
        }

        Arr::set($this->params, 'currCode', $currency);
    }

    /**
     * Set payment method
     * ALL - all available
     * CC - credit card
     * BancNet - BancNet debit
     * PAYCASH - Over the counter partners
     *   
     * @param string $method 
     */
    public function setPayMethod(string $method)
    {
        Arr::set($this->params, 'payMethod', $method);
    }

    /**
     * Set amount
     * 
     * @param  float $amount 
     * @return void
     */
    public function setAmount(float $amount)
    {
        Arr::set($this->params, 'amount', $amount);
    }

    /**
     * Set return urls
     * 
     * @param string $success   
     * @param string $cancelled 
     * @param string $failed    
     * @return void
     */
    public function setReturnUrls(string $success, string $cancelled, string $failed)
    {
        Arr::set($this->params, 'successUrl', $success);
        Arr::set($this->params, 'cancelUrl', $cancelled);
        Arr::set($this->params, 'failUrl', $failed);
    }
    
    /**
     * Set payment type
     * N - Normal payment (sales)
     * H - Hold payment (authorize)
     * 
     * @param string $type
     * @return void
     */
    public function setPayType(string $type)
    {
        Arr::set($this->params, 'payType');
    }

    /**
     * Generate secure hash
     * 
     * @return string 
     */
    private function generateSecureHash()
    {
        $str = Arr::get($this->params, 'merchantId') .'|'. Arr::get($this->params, 'orderRef') .'|'. 
            Arr::get($this->params, 'currCode') .'|'. Arr::get($this->params, 'amount') .'|'. 
            Arr::get($this->params, 'payType') .'|'. $this->hashSecret;

        return sha1($str);   
    }

    /**
     * Proceed to asiapay payment page
     * 
     * @param  void
     * @return void 
     */
    public function send()
    {
        $form = 'form-'. time();

        echo '<form action="'. $this->url .'" method="post" id="'. $form .'" name="'. $form .'">';

        if ($this->hashSecret) {
            echo '<input type="hidden" name="secureHash" value="'. $this->generateSecureHash() .'">';    
        }
        
        foreach($this->params as $field => $value) {
            echo '<input type="hidden" name="'. $field .'" value="'. $value .'">';
        }

        echo '</form>';

        echo '<script type="text/javascript">';
        echo 'document.getElementById("'. $form .'").submit()';
        echo '</script>';
    }
}