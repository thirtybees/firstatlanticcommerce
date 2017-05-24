<?php
/**
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class FirstAtlanticCommercePaymentModuleFrontController
 */
class FirstAtlanticCommercePaymentModuleFrontController extends ModuleFrontController
{
    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        $live = (bool) Configuration::get(FirstAtlanticCommerce::GO_LIVE);

        // Useful for generation of test Order numbers
        // You would use REAL order numbers in an Integration
        // How to sign an FAC Authorize message in PHP


        // FAC Integration Domain
        if ($live) {
            $domain = 'marlin.firstatlanticcommerce.com';
        } else {
            $domain = 'ecm.firstatlanticcommerce.com';
        }

        // Ensure you append the ?wsdl query string to the URL
        $wsdlurl = "https://$domain/PGService/HostedPage.svc?wsdl";
        $soapUrl = "https://$domain/PGService/HostedPage.svc";
        // Set up client to use SOAP 1.1 and NO CACHE for WSDL. You can choose between
        // exceptions or status checking. Here we use status checking. Trace is for Debug only
        // Works better with MS Web Services where
        // WSDL is split into several files. Will fetch all the WSDL up front.
        $options = [
            'location'     => $soapUrl,
            'soap_version' => SOAP_1_1,
            'exceptions'   => 0,
            'trace'        => 1,
            'cache_wsdl'   => WSDL_CACHE_NONE,
        ];
        // WSDL Based calls use a proxy, so this is the best way
        // to call FAC PG Operations.
        $client = new SoapClient($wsdlurl, $options);
        // This should not be in your code in plain text!
        $password = $live ? Configuration::get(FirstAtlanticCommerce::MERCHANT_PASSWORD_LIVE) : Configuration::get(FirstAtlanticCommerce::MERCHANT_PASSWORD_TEST);
        // Use your own FAC ID
        $facId = $live ? Configuration::get(FirstAtlanticCommerce::MERCHANT_ID_LIVE) : Configuration::get(FirstAtlanticCommerce::MERCHANT_ID_TEST);
        //$facId = '70700001';
        // Acquirer is always this
        $acquirerId = '464748';
        // Must be Unique per order. Put your own format here
        $orderNumber = 'tb_fac_'.$this->context->cart->id;
        // THESE next variables COME FROM THE PREVIOUS PAGE (hence $_POST) but you could drive these from
        // any source such as config files, server cache etc.
        // Passed in as a decimal but 12 chars is required
        $facAmount = $this->context->cart->getOrderTotal();
        $decimals = 0;
        if (!in_array(Tools::strtolower($this->context->currency->iso_code), FirstAtlanticCommerce::$zeroDecimalCurrencies)) {
            $facAmount = (int) ($facAmount * 100);
            $decimals = 2;
        }
        // Page Set
        $pageset = $live ? Configuration::get(FirstAtlanticCommerce::PAGE_SET_NAME_LIVE) : Configuration::get(FirstAtlanticCommerce::PAGE_SET_NAME_TEST);
        // Page Name
        $pagename = $live ? Configuration::get(FirstAtlanticCommerce::PAGE_NAME_LIVE) : Configuration::get(FirstAtlanticCommerce::PAGE_NAME_TEST);
        // TransCode
        $transCode = 0;
        // Formatted Amount. Must be in twelve charecter, no decimal place, zero padded format
        $amountFormatted = str_pad(''.$facAmount, 12, "0", STR_PAD_LEFT);
        // 840 = USD, put your currency code here
        $currency = $this->context->currency->iso_code_num;
        // Each call must have a signature with the password as the shared secret
        $signature = $this->sign($password, $facId, $acquirerId, $orderNumber, $amountFormatted, $currency);
        // You only need to initialise the message sections you need. So for a basic Auth

        // only Credit Cards and Transaction details are required.
        // Transaction Details.
        // Where the response will end up. SHould be a page your site and will get two parameters
        // ID = Single Use Key passed to payment page and RespCode = normal response code for Auth
        // The request data is named 'Request' for reasons that are not clear!
        $hostedPageRequest = [
            'Request' => [
                'TransactionDetails'    => [
                    'AcquirerId'       => $acquirerId,
                    'Amount'           => $amountFormatted,
                    'Currency'         => $currency,
                    'CurrencyExponent' => $decimals,
                    'IPAddress'        => '',
                    'MerchantId'       => $facId,
                    'OrderNumber'      => $orderNumber,
                    'Signature'        => $signature,
                    'SignatureMethod'  => 'SHA1',
                    'TransactionCode'  => $transCode,
                ],
                'CardHolderResponseURL' => $this->context->link->getModuleLink($this->module->name, 'confirmation', [], true),
            ],
        ];

        // Call the Authorize through the Soap Client
        $result = $client->HostedPageAuthorize($hostedPageRequest);
        // You should CHECK the results here!!!
        // Extract Token
        $token = $result->HostedPageAuthorizeResult->SingleUseToken;
        // Construct the URL. This may be different for Production. Check with FAC
        $paymentPageUrl = "https://$domain/MerchantPages/$pageset/$pagename/";
        // Create the location header to effect a redirect. Add token required by page
        $redirectUrl = $paymentPageUrl.$token;
        // Redirect user to the Payment page

        Tools::redirectLink($redirectUrl);
    }

    protected function sign($passwd, $facId, $acquirerId, $orderNumber, $amount, $currency)
    {
        $stringtohash = $passwd.$facId.$acquirerId.$orderNumber.$amount.$currency;
        $hash = sha1($stringtohash, true);
        $signature = base64_encode($hash);

        return $signature;
    }

    protected function msTimeStamp()
    {
        return (string) round(microtime(1) * 1000);
    }
}
