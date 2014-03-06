<?php
namespace Karser\RobokassaBundle\Controller;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RobokassaController extends Controller
{
    public function callbackAction(Request $request)
    {
        $out_sum = $request->get('OutSum');
        $inv_id = $request->get('InvId');
        $sign = $request->get('SignatureValue');
        if (!$this->get('karser.robokassa.client.auth')->validateResult($sign, $out_sum, $inv_id)) {
            return new Response('FAIL', 500);
        }

        $instruction = $this->getInstruction($inv_id);

        /** @var FinancialTransactionInterface $transaction */
        if (null === $transaction = $instruction->getPendingTransaction()) {
            return new Response('FAIL', 500);
        }

        try {
            $this->get('payment.plugin_controller')->approveAndDeposit($transaction->getPayment()->getId(), $out_sum);
        } catch (\Exception $e) {
            return new Response('FAIL', 500);
        }
        $this->getDoctrine()->getManager()->flush();

        return new Response('OK' . $inv_id);
    }

    public function successAction(Request $request)
    {
        $out_sum = $request->get('OutSum');
        $inv_id = $request->get('InvId');
        $sign = $request->get('SignatureValue');
        if (!$this->get('karser.robokassa.client.auth')->validateSuccess($sign, $out_sum, $inv_id)) {
            return new Response('FAIL', 500);
        }

        $instruction = $this->getInstruction($inv_id);
        $data = $instruction->getExtendedData();
        return $this->redirect($data->get('return_url'));
    }

    public function failAction(Request $request)
    {
        $inv_id = $request->get('InvId');
        $instruction = $this->getInstruction($inv_id);
        $data = $instruction->getExtendedData();
        return $this->redirect($data->get('cancel_url'));
    }

    private function getInstruction($id)
    {
        $instruction = $this->getDoctrine()->getManager()->getRepository('JMSPaymentCoreBundle:PaymentInstruction')->find($id);
        if (empty($instruction)) {
            throw new \Exception('Cannot find instruction id='.$id);
        }
        return $instruction;
    }
}
