<?php
namespace Karser\RobokassaBundle\Client;

use Guzzle\Http\Client as Guzzle;

use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\BlockedException;
use Sabre\XML\Reader;

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

    public function getHost()
    {
        return $this->test ? 'test.robokassa.ru/Index.aspx' : 'test.robokassa.ru/Index.aspx';
    }

    private function getXmlMethodUrl($method)
    {
        $url = $this->test ? 'test.robokassa.ru/Webservice/Service.asmx' : 'robokassa.ru/Webservice/Service.asmx';
        return 'http://' . $url . '/' . $method;
    }

    public function getRedirectUrl(FinancialTransactionInterface $transaction)
    {
        /** @var PaymentInstructionInterface $instruction */
        $instruction = $transaction->getPayment()->getPaymentInstruction();
        $inv_id = $instruction->getId();
        /** @var ExtendedDataInterface $data */
        $data = $transaction->getExtendedData();
        $data->set('inv_id', $inv_id);
        $parameters = [
            'MrchLogin' => $this->login,
            'OutSum' => $transaction->getRequestedAmount(),
            'InvId' => $inv_id,
            'Desc' => 'test desc',
            'IncCurrLabel' => '',
            'SignatureValue' => $this->auth->sign($this->login, $transaction->getRequestedAmount(), $inv_id)
        ];

        return sprintf('http://%s?%s', $this->getHost(), http_build_query($parameters));
    }

    private function post($uri, array $parameters = [])
    {
        $guzzle  = new Guzzle($uri);
        $request = $guzzle->post(null, null, $parameters);
        return $request->send();
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

        $url = $this->getXmlMethodUrl('OpState');
        $result = $this->sendXMLRequest($url, $params);
        return (int)$result->State->Code;
    }
}