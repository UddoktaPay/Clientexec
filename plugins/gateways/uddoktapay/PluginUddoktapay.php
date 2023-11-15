<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'plugins/gateways/uddoktapay/UddoktaPay.php';

class Pluginuddoktapay extends GatewayPlugin
{
    function getVariables()
    {
        $variables = [
            lang("Plugin Name") => [
                "type"          => "hidden",
                "description"   => "",
                "value"         => "UddoktaPay"
            ],
            lang('Signup Name') => [
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'UddoktaPay'
            ],
            lang("API KEY") => [
                "type"          => "text",
                "description"   => "Enter your API KEY",
                "value"         => ""
            ],
            lang("API URL") => [
                "type"          => "text",
                "description"   => "Enter your API URL",
                "value"         => ""
            ]
        ];
        return $variables;
    }
    function singlepayment($params)
    {
        $invoiceId = $params['invoiceNumber'];
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));
        $firstname = $params['userFirstName'];
        $lastname = $params['userLastName'];
        $email = $params['userEmail'];
        $bar = "/";
        if (substr(CE_Lib::getSoftwareURL(), -1) == "/") {
            $bar = "";
        }
        $baseURL = CE_Lib::getSoftwareURL() . $bar;
        $callbackURL = $baseURL . "plugins/gateways/uddoktapay/callback.php";
        $currencyCode = $params['userCurrency'];
        $cancelURL = $params['invoiceviewURLCancel'];

        $apiKey = $params['plugin_uddoktapay_API KEY'];
        $apiBaseURL = $params['plugin_uddoktapay_API URL'];
        $uddoktaPay = new UddoktaPay($apiKey, $apiBaseURL);

        $requestData = [
            'full_name'     => $firstname . " " . $lastname,
            'email'         => $email,
            'amount'        => $amount,
            'metadata'      => [
                'invoice_id'    => $invoiceId,
                'currency'      => $currencyCode
            ],
            'redirect_url'  => $callbackURL,
            'return_type'   => 'GET',
            'cancel_url'    => $cancelURL,
            'webhook_url'   => $callbackURL
        ];

        try {
            $paymentUrl = $uddoktaPay->initPayment($requestData);
            header('Location:' . $paymentUrl);
            exit();
        } catch (Exception $e) {
            die("Initialization Error: " . $e->getMessage());
        }
    }
    function credit($params)
    {
    }
}
