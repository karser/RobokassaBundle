<?php
namespace Karser\RobokassaBundle\Client;

use GuzzleHttp\Client as Guzzle;

use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\BlockedException;

class Client
{

    /** @var  Auth */
    private $auth;

    /** @var string */
    private $login;

    /** @var  bool */
    private $test;


    public function __construct(Auth $auth, $login, $test)
    {
        $this->auth = $auth;
        $this->login = $login;
        $this->test = $test;
    }

    private function getWebServerUrl()
    {
        return $this->test ? 'http://test.robokassa.ru/Index.aspx' : 'https://auth.robokassa.ru/Merchant/Index.aspx';
    }

    private function getXmlServerUrl()
    {
        return $this->test ? 'http://test.robokassa.ru/Webservice/Service.asmx' : 'https://merchant.roboxchange.com/WebService/Service.asmx';
    }

    public function getRedirectUrl(FinancialTransactionInterface $transaction)
    {
        /** @var PaymentInstructionInterface $instruction */
        $instruction = $transaction->getPayment()->getPaymentInstruction();
        $inv_id = $instruction->getId();
        /** @var ExtendedDataInterface $data */
        $data = $transaction->getExtendedData();
        $data->set('inv_id', $inv_id);
        
        $description = 'test desc';
        if($data->has('description')) {
            $description = $data->get('description');
        }
        
        $parameters = [
            'MrchLogin' => $this->login,
            'OutSum' => $transaction->getRequestedAmount(),
            'InvId' => $inv_id,
            'Desc' => $description,
            'IncCurrLabel' => '',
            'SignatureValue' => $this->auth->sign($this->login, $transaction->getRequestedAmount(), $inv_id)
        ];

        return $this->getWebServerUrl() .'?' . http_build_query($parameters);
    }

    private function post($uri, array $parameters = [])
    {
        $guzzle  = new Guzzle();
        return $guzzle->post($uri, [ 'body' => $parameters ]);
    }

    private function sendXMLRequest($url, $params = [])
    {
        $url = sprintf('%s?%s', $url, http_build_query($params));
        $response = $this->post($url, $params);
        $xml = new \SimpleXMLElement($response->getBody());
        $result_code = (int) $xml->Result->Code;
        if ($result_code !== 0) {
            throw new BlockedException("Awaiting extended data");
        }
        return $xml;
    }

    public function requestOpState($inv_id)
    {
        $params = [
            'MerchantLogin' => $this->login,
            'InvoiceID' => $inv_id,
            'Signature' => $this->auth->signXML($this->login, $inv_id),
        ];
        if ($this->test) {
            $params['StateCode'] = 100;
        }

        $url = $this->getXmlServerUrl() . '/' . 'OpState';
        $result = $this->sendXMLRequest($url, $params);
        return (int)$result->State->Code;
    }
}