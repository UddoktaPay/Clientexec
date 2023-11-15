<?php

require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'plugins/gateways/uddoktapay/UddoktaPay.php';

class PluginUddoktaPayCallback extends PluginCallback
{
    public function processCallback()
    {
        $cPlugin = new Plugin('', 'uddoktapay', $this->user);
        $apiKey = trim($cPlugin->GetPluginVariable("plugin_uddoktapay_API KEY"));
        $apiBaseURL = trim($cPlugin->GetPluginVariable("plugin_uddoktapay_API URL"));
        $uddoktaPay = new UddoktaPay($apiKey, $apiBaseURL);

        try {
            if (isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id'])) {
                $response = $uddoktaPay->verifyPayment($_REQUEST['invoice_id']);
            } else {
                $response = $uddoktaPay->executePayment();
            }
        } catch (Exception $e) {
            die("Verification Error: " . $e->getMessage());
        }

        $amount = trim($response['amount']);
        $paymentMethod = trim(strtoupper($response['payment_method']));
        $invoiceId = trim($response['metadata']['invoice_id']);
        $currencyCode = $response['metadata']['currency'];
        $exchangeRate = !empty($cPlugin->GetPluginVariable("plugin_uddoktapay_Exchange Rate")) ? $cPlugin->GetPluginVariable("plugin_uddoktapay_Exchange Rate") : 1;

        if ($currencyCode !== 'BDT') {
            $amount /= $exchangeRate;
        }

        $price = $amount . " " . $currencyCode;
        $cPlugin = new Plugin($invoiceId, 'uddoktapay', $this->user);
        $cPlugin->setAmount($amount);
        $cPlugin->setAction('charge');

        $status = trim($response['status']);

        if ($status === 'COMPLETED') {
            $transaction = "$paymentMethod payment of $price Successful (Order ID: " . $invoiceId . ")";
            // Create plug in class to interact with CE
            if ($cPlugin->IsUnpaid() == 1) {
                $cPlugin->PaymentAccepted($amount, $transaction);
                $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $invoiceId;
                header("Location: " . $returnURL);
                exit;
            } else {
                return;
            }
        } else {
            $transaction = "$paymentMethod payment of $price Failed (Order ID: " . $invoiceId . ")";
            $cPlugin->PaymentRejected($transaction);
            $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&cancel=1&controller=invoice&view=invoice&id=" . $invoiceId;
            header("Location: " . $returnURL);
            exit;
        }
    }
}
