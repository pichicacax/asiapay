<?php

namespace Pichicacax\Asiapay;

use Illuminate\Support\Arr;
use \UnexpectedValueException;

class Datafeed
{
    protected $hashSecret = '';

    // @var array Post params returned by Asiapay
    protected $params = [];

    protected $prc_errors = [
        '1' => 'Rejected by Payment Bank',
        '3' => 'Rejected due to Payer Authentication Failure (3D)',
        '-1' => 'Rejected due to Input Parameters Incorrect',
        '-2' => 'Rejected due to Server Access Error',
        '-8' => 'Rejected due to PesoPay Internal/Fraud Prevention Checking',
        '-9' => 'Rejected by Host Access Error',
    ];

    public function __construct(string $hashSecret = '', array $params = [])
    {
        if (! empty($hashSecret)) {
            $this->setHashSecret($hashSecret);
        }

        if (! empty($params)) {
            $this->setParams($params);
        }
    }

    /**
     * Set params
     * 
     * @param  array $params 
     * @return void
     */
    public function setParams(array $params)
    {
        $this->params = $params;
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
     * Generate secure hash
     * 
     * @return string 
     */
    public function generateSecureHash()
    {
        $str = Arr::get($this->params, 'src', '') .'|'. Arr::get($this->params, 'prc', '') .'|'.    
            Arr::get($this->params, 'successcode', '') .'|'. Arr::get($this->params, 'Ref', '') .'|'.
            Arr::get($this->params, 'PayRef', '') .'|'. Arr::get($this->params, 'Cur', '') .'|'.
            Arr::get($this->params, 'Amt', '') .'|'. Arr::get($this->params, 'payerAuth', '') .'|'.
            $this->hashSecret;

        return sha1($str);
    }

    /**
     * Verify integrity of datafeed.
     * $_POST['secureHash'] == generated secure hash
     * 
     * @return boolean|void 
     * @throws \UnexpectedValueException 
     */
    public function verify()
    {
        if (Arr::get($this->params, 'secureHash', '') !== $this->generateSecureHash()) {
            throw new UnexpectedValueException('Secure hash mismatch.');
        }

        return true;
    }

    /**
     * Check PRC
     * Return bank host status code (primary)
     * 
     * @return boolean|void
     * @throws \UnexpectedValueException 
     */
    public function checkPRC()
    {
        $prc = Arr::get($this->params, 'prc', 'XX');

        if ($prc != 0) {
            throw new UnexpectedValueException(Arr::get($this->prc_errors, $prc));
        }

        return true;
    }

    /**
     * Check success code
     * 0 - succeeded, 1 - failure, Others - error
     * 
     * @return boolean|void 
     * @throws \UnexpectedValueException 
     */
    public function checkSuccessCode()
    {
        $successCode = Arr::get($this->params, 'successcode', 'XX');

        if ($successCode != 0) {
            throw new UnexpectedValueException('Transaction failure.');
        }

        return true;
    }

    /**
     * Check alert code risk level
     * 
     * @return boolean|void 
     * @throws \UnexpectedValueException 
     */
    public function checkAlertCode()
    {
        $alertCode = Arr::get($this->params, 'AlertCode', '');

        if (! $alertCode) return true;

        $prefix = substr($alertCode, 0, 1);
        
        if ($prefix == 'O') {
            throw new UnexpectedValueException('Medium risk transaction.');
        } else if ($prefix == 'R') {
            throw new UnexpectedValueException('High risk transaction.');
        }

        return true;
    }
}