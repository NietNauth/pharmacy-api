<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoService
{
    public function createPaymentUrl($order)
    {
        $endpoint = config('momo.endpoint');
        $partnerCode = config('momo.partner_code');
        $accessKey = config('momo.access_key');
        $secretKey = config('momo.secret_key');
        
        $orderInfo = "Thanh toan don hang " . $order->order_code;
        $amount = (string)round($order->total);
        $orderId = $order->order_code . '_' . time();
        $requestId = time() . "";
        $returnUrl = config('momo.return_url');
        $notifyUrl = config('momo.notify_url');

        if (empty($returnUrl)) {
            $returnUrl = 'https://pharmacy-web-eight.vercel.app/checkout/momo-return';
        }
        if (empty($notifyUrl)) {
            $notifyUrl = 'https://pharmacy-api-ak90.onrender.com/api/v1/momo-ipn';
        }

        $extraData = "";
        $requestType = "payWithMethod";
        $orderGroupId = "";
        $autoCapture = true;

        // Exact signature string from your sample
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $notifyUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $returnUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = array(
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => 'MomoTestStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'requestType' => $requestType,
            'ipnUrl' => $notifyUrl,
            'lang' => 'vi',
            'redirectUrl' => $returnUrl,
            'autoCapture' => $autoCapture,
            'extraData' => $extraData,
            'orderGroupId' => $orderGroupId,
            'signature' => $signature
        );

        $response = Http::post($endpoint, $data);

        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['payUrl'])) {
                return $json['payUrl'];
            }
            Log::error('MoMo Create Payment Failed', $json);
        } else {
            Log::error('MoMo Create Payment HTTP Failed', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return null;
    }

    public function validateResponse($inputData)
    {
        $secretKey = config('momo.secret_key');
        $accessKey = config('momo.access_key');
        $signature = $inputData['signature'] ?? '';
        
        // Verified formula: accessKey at the start + alphabetical parameters
        $params = [
            'amount' => $inputData['amount'] ?? '',
            'extraData' => $inputData['extraData'] ?? '',
            'message' => $inputData['message'] ?? '',
            'orderId' => $inputData['orderId'] ?? '',
            'orderInfo' => $inputData['orderInfo'] ?? '',
            'orderType' => $inputData['orderType'] ?? '',
            'partnerCode' => $inputData['partnerCode'] ?? '',
            'payType' => $inputData['payType'] ?? '',
            'requestId' => $inputData['requestId'] ?? '',
            'responseTime' => $inputData['responseTime'] ?? '',
            'resultCode' => $inputData['resultCode'] ?? '',
            'transId' => $inputData['transId'] ?? '',
        ];

        ksort($params);
        $dataToSign = "accessKey=" . $accessKey;
        foreach ($params as $key => $value) {
            $dataToSign .= "&" . $key . "=" . $value;
        }

        $newSignature = hash_hmac("sha256", $dataToSign, $secretKey);
        
        return hash_equals($newSignature, $signature);
    }
}
