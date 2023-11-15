<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'plugins/gateways/uddoktapay/UddoktaPay.php';

class PluginuddoktapayCallback extends PluginCallback
{
    function processCallback()
    {
        if (isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id'])) {
            $cPlugin = new Plugin('', 'uddoktapay', $this->user);
            $invoice_id = $_REQUEST['invoice_id'];
            $apiKey = trim($cPlugin->GetPluginVariable("plugin_uddoktapay_API KEY"));
            $apiBaseURL = trim($cPlugin->GetPluginVariable("plugin_uddoktapay_API URL"));
            $uddoktaPay = new UddoktaPay($apiKey, $apiBaseURL);
            try {
                $response = $uddoktaPay->verifyPayment($invoice_id);
            } catch (Exception $e) {
                die("Verification Error: " . $e->getMessage());
            }

            $amount = trim($response['amount']);
            $payment_method = trim($response['payment_method']);
            $invoiceId = trim($response['metadata']['invoice_id']);
            $currencyCode = $response['metadata']['currency'];
            $price = $amount . " " . $currencyCode;

            $cPlugin = new Plugin($invoiceId, 'uddoktapay', $this->user);
            $cPlugin->setAmount($amount);
            $cPlugin->setAction('charge');

            if (trim($response['status']) === 'COMPLETED') {
                //Create plug in class to interact with CE
                if ($cPlugin->IsUnpaid() == 1) {
                    $transaction = "$payment_method payment of $price Successful (Order ID: " . $invoiceId . ")";
                    $cPlugin->PaymentAccepted($amount, $transaction);
                    $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $invoiceId;
                    header("Location: " . $returnURL);
                    exit;
                } else {
                    return;
                }
            } else {
                $transaction = "$payment_method payment of $price Failed (Order ID: " . $invoiceId . ")";
                $cPlugin->PaymentRejected($transaction);
                $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&cancel=1&controller=invoice&view=invoice&id=" . $invoiceId;
                header("Location: " . $returnURL);
                exit;
            }
            return;
        } else {
            $cPlugin = new Plugin('', 'uddoktapay', $this->user);
            $apiKey = trim($cPlugin->GetPluginVariable("plugin_uddoktapay_API KEY"));
            $apiBaseURL = trim($cPlugin->GetPluginVariable("plugin_uddoktapay_API URL"));
            $uddoktaPay = new UddoktaPay($apiKey, $apiBaseURL);
            try {
                $response = $uddoktaPay->executePayment();
            } catch (Exception $e) {
                die("Verification Error: " . $e->getMessage());
            }

            $amount = trim($response['amount']);
            $payment_method = trim(strtoupper($response['payment_method']));
            $invoiceId = trim($response['metadata']['invoice_id']);
            $currencyCode = $response['metadata']['currency'];
            $price = $amount . " " . $currencyCode;

            $cPlugin = new Plugin($invoiceId, 'uddoktapay', $this->user);
            $cPlugin->setAmount($amount);
            $cPlugin->setAction('charge');

            if (trim($response['status']) === 'COMPLETED') {
                //Create plug in class to interact with CE
                if ($cPlugin->IsUnpaid() == 1) {
                    $transaction = "$payment_method payment of $price Successful (Order ID: " . $invoiceId . ")";
                    $cPlugin->PaymentAccepted($amount, $transaction);
                    exit;
                } else {
                    return;
                }
            } else {
                $transaction = "$payment_method payment of $price Failed (Order ID: " . $invoiceId . ")";
                $cPlugin->PaymentRejected($transaction);
                exit;
            }
            return;
        }
        return;
    }
}
