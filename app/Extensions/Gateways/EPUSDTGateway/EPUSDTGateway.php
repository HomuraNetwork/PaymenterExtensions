<?php

namespace App\Extensions\Gateways\EPUSDTGateway;

use App\Classes\Extensions\Gateway;
use App\Helpers\ExtensionHelper;

class EPUSDTGateway extends Gateway
{

    public function getMetadata()
    {
        return [
            'display_name' => 'EPUSDT Gateway',
            'version' => '2024.01.11',
            'author' => 'Homura Network Limited',
            'website' => 'https://homura.network',
        ];
    }

    public function getConfig()
    {
        return [
            [
                'name' => 'sign_key',
                'friendlyName' => 'Sign Key',
                'type' => 'text',
                'required' => true,
                'description' => 'EPUSDT Sign Key'
            ],
            [
                'name' => 'epusdt_endpoint',
                'friendlyName' => 'EPUSDT Endpoint',
                'type' => 'text',
                'required' => true,
                'description' => 'EPUSDT Endpoint'
            ],
            [
                'name' => 'paymenter_baseurl',
                'friendlyName' => 'Paymenter Base URL',
                'type' => 'text',
                'required' => true,
                'description' => 'Base URL for call back'
            ],
        ];
    }

    public function pay($total, $products, $invoiceId)
    {
        $apiKey = ExtensionHelper::getConfig('EPUSDTGateway', 'sign_key');
        $apiEndpoint = ExtensionHelper::getConfig('EPUSDTGateway', 'epusdt_endpoint');
        $serviceDomain = ExtensionHelper::getConfig('EPUSDTGateway', 'paymenter_baseurl');

        $notifyUrl = $serviceDomain . '/extensions/epusdt/webhook';
        $invoiceUrl = $serviceDomain . '/invoices/' . $invoiceId;

        $timestamp = round(microtime(true) * 1000);
        $uniqueOrderId = $timestamp . "|" . $invoiceId . "|" . $total;

        $str = 'amount=' . $total . '&notify_url=' . $notifyUrl . '&order_id=' . $uniqueOrderId . '&redirect_url=' . $invoiceUrl . $apiKey;
        $signature = md5($str);


        $data = json_encode(array(
            'order_id' =>  (string)  $uniqueOrderId,
            'amount' => $total,
            'notify_url' => $notifyUrl,
            'redirect_url' => $invoiceUrl,
            'signature' => $signature
        ));


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiEndpoint . '/api/v1/order/create-transaction');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("content-type: application/json"));
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result);

        if (!$response || (isset($response->status_code) && $response->status_code != 200)) {
            $errorMessage = isset($response) ? json_encode($response) : 'Unknow Error';
            header('Location: /extensions/epusdt/error?message=' . urlencode($errorMessage));
            exit;
        }

        return $response->data->payment_url ?? '';
    }

    public function EPUSDT_webhook($request)
    {
        $apiSecret = ExtensionHelper::getConfig('EPUSDTGateway', 'sign_key');
        $data = $request->json()->all();

        if (!$this->verifyEPUSDTSignature($data, $apiSecret)) {
            return response('Invalid signature', 401); // Unauthorized
        }

        if (isset($data['status']) && $data['status'] == 2) {
            $uniqueOrderId = $data['order_id'];
            $orderParts = explode('|', $uniqueOrderId);
            $invoiceId = $orderParts[1];

            ExtensionHelper::paymentDone($invoiceId);
        }

        return response('ok');
    }

    private function verifyEPUSDTSignature($data, $secret)
    {
        if (!isset($data['signature'])) {
            return false;
        }

        $signature = $data['signature'];
        unset($data['signature']);
        $generatedSignature = $this->epusdtSign($data, $secret);
        return $generatedSignature === $signature;
    }

    public function epusdtSign($params, $apiToken)
    {
        $params = array_filter($params, function ($value) {
            return ($value !== null && $value !== '');
        });

        ksort($params);
        $paramString = '';
        foreach ($params as $key => $value) {
            if ($paramString != '') {
                $paramString .= '&';
            }
            $paramString .= "$key=$value";
        }

        $paramString .= $apiToken;


        return strtolower(md5($paramString));
    }
}
