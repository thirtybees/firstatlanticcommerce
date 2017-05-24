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
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class FirstAtlanticCommerceConfirmationModuleFrontController
 */
class FirstAtlanticCommerceConfirmationModuleFrontController extends ModuleFrontController
{
    const RESPONSE_CODE_APPROVED = 1;
    const RESPONSE_CODE_DECLINED = 2;
    const RESPONSE_CODE_ERROR = 3;

    /** @var FirstAtlanticCommerce $module */
    public $module;

    /**
     * FirstAtlanticCommerceConfirmationModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * Post process
     *
     * @return bool Indicates that the info has been successfully processed
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $this->context->smarty->assign(
            [
                'orderLink' => $this->context->link->getPageLink($orderProcess, true),
            ]
        );

        if (!Tools::isSubmit('ID') || !Tools::isSubmit('RespCode') || !Tools::isSubmit('ReasonCode')) {
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'confirmation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        $cart = $this->context->cart;
        $customer = new Customer((int) $cart->id_customer);
        $currency = new Currency((int) $cart->id_currency);

        if (Configuration::get(FirstAtlanticCommerce::GO_LIVE)) {
            $host = 'marlin.firstatlanticcommerce.com';
        } else {
            $host = 'ecm.firstatlanticcommerce.com';
        }
        // Ensure you append the ?wsdl query string to the URL for WSDL URL
        $wsdlurl = "https://$host/PGService/HostedPage.svc?wsdl";
        // No WSDL parameter for location URL
        $soapUrl = "https://$host/PGService/HostedPage.svc";
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
        // to call FAC PG Operations as it creates the methods for you
        $client = new SoapClient($wsdlurl, $options);
        // Call the HostedPageResults through the Client. Note the param
        // name is case sensitive, so 'Key' does not work.
        $result = $client->HostedPageResults(['key' => Tools::getValue('ID')]);

        // NOW: You have access to all the response fields and can evaluate as you want to
        // and use them to display something to the user in an HTML page like the HTML snippet
        // below. It's very simple and you have not had any exposure to the card number at all.
        // While it is not necessary to make this soap call, it is advisable that you implement this
        // and get the full response details to ensure the correct amount has been charged etc.
        // You should also store the results in case of any chargeback issues and to check the response
        // code has not been tampered with.
        if (!isset($result->HostedPageResultsResult->AuthResponse->CreditCardTransactionResults->ResponseCode)) {
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'confirmation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: No response code found when trying to verify the hosted page result", 3);

            return false;
        }

        $responseCode = (int) $result->HostedPageResultsResult->AuthResponse->CreditCardTransactionResults->ResponseCode;
        if ($responseCode === static::RESPONSE_CODE_DECLINED) {
            switch ($result->HostedPageResultsResult->AuthResponse->CreditCardTransactionResults->ReasonCode) {
                case 35:
                    $this->errors[] = $this->module->l('Unable to process your request. Please try again later.', 'confirmation');
                    break;
                case 38:
                    $this->errors[] = $this->module->l('Transaction processing terminated. Please try again later.');
                    break;
                case 39:
                    $this->errors[] = $this->module->l('Issuer or switch not available. Please try again later.');
                    break;
                default:
                    $this->errors[] = $this->module->l('Transaction is declined.');
                    break;

            }

            $this->setTemplate('error.tpl');

            return false;
        } elseif ($responseCode === static::RESPONSE_CODE_ERROR) {
            switch ($result->HostedPageResultsResult->AuthResponse->CreditCardTransactionResults->ReasonCode) {
                case 5:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 5 - Connection not secured", 3);
                    break;
                case 6:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 6 - HTTP Method not POST", 3);
                    break;
                case 7:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 7 - A field is missing", 3);
                    break;
                case 8:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 8 - Field format is invalid", 3);
                    break;
                case 10:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 10 - Invalid Merchant", 3);
                    break;
                case 11:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 11 - Failed Authentication (Signature computed incorrectly)", 3);
                    break;
                case 12:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 12 - Merchant is inactive", 3);
                    break;
                case 14:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 14 - Merhcnat is not allowed to process this currency", 3);
                    break;
                case 15:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 15 - Merchant settings are not valid", 3);
                    break;
                case 16:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 16 - Unable to process transaction", 3);
                    break;
                case 36:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 36 - Credit Cardholder canceled the request", 3);
                    break;
                case 37:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 37 - Card Entry Retry Count exited allowed limit", 3);
                    break;
                case 40:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 40 - Duplicate Order Not Allowed", 3);
                    break;
                case 42:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 42 - Illegal Operation by Card Holder. Check Order Status.", 3);
                    break;
                case 60:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 60 - Duplicate Order Not Allowed", 3);
                    break;
                case 90:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 90 - General Error during processing. Please try again later.", 3);
                    break;
                case 98:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 98 - System is temporarily down. Try later.", 3);
                    break;
                case 401:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 401 - Cycle interrupted by the user or client/browser connection not available.", 3);
                    break;
                case 994:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 994 - FACPGWS BeginTransactionStatus Failure", 3);
                    break;
                case 995:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 995 - FACPGWS EndTransactionStatusFailure", 3);
                    break;
                case 996:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 996 - Not a web-based transaction", 3);
                    break;
                case 997:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 997 - FACPGAppWS Failure", 3);
                    break;
                case 998:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 998 - Missing Parameter", 3);
                    break;
                case 999:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 999 - No Response", 3);
                    break;
                case 1001:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1001 - FACPGWS Invalid Protocol. Only HTTPS Allowed", 3);
                    break;
                case 1002:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1002 - Missing Parameter(2)", 3);
                    break;
                case 1003:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1003 - Invalid Parameters Settings", 3);
                    break;
                case 1004:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1004 - Invalid Amount. Not 12 characters in length", 3);
                    break;
                case 1010:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1010 - FACPGWS Authorize HTTP Response not OK", 3);
                    break;
                case 1020:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1020 - FACPGWS Authorize Failure", 3);
                    break;
                case 1030:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1030 - FACPG BeginCRRError", 3);
                    break;
                case 1031:
                    Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: 1031 - FACPG EndCRRError", 3);
                    break;
                default:
                    break;
            }

            $this->errors[] = $this->module->l('An error occurred. Please contact us for more information.', 'confirmation');
            $this->setTemplate('error.tpl');

            Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: No response code found when trying to verify the hosted page result", 3);

            return false;
        } elseif ($responseCode !== static::RESPONSE_CODE_APPROVED) {
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'confirmation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        $actualAmount = json_decode($result->HostedPageResultsResult->AuthResponse->CreditCardTransactionResults);
        $actualAmount = (float) $actualAmount['cart_amount'];

        $shouldBeAmount = (float) $this->context->cart->getOrderTotal();

        if ($actualAmount !== $shouldBeAmount) {
            /**
             * An error occurred and is shown on a new page.
             */
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'confirmation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: Actual amount mismatch - Cart amount: $shouldBeAmount - Actual amount: $actualAmount", 3);
        }

        /**
         * Converting cart into a valid order
         */
        $idCurrency = (int) $currency->id;

        if ($this->module->validateOrder($cart->id, (int) Configuration::get(FirstAtlanticCommerce::STATUS_VALIDATED), $cart->getOrderTotal(), 'Credit Card', null, [], $idCurrency, false, $cart->secure_key)) {
            /**
             * If the order has been validated we try to retrieve it
             */
            $idOrder = Order::getOrderByCartId((int) $cart->id);

            /**
             * The order has been placed so we redirect the customer on the confirmation page.
             */
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$idOrder.'&key='.$customer->secure_key);
        }

        /**
         * An error occurred and is shown on a new page.
         */
        $error = $this->module->l('An error occurred. Please contact us for more information.', 'confirmation');
        $this->errors[] = $error;
        $this->setTemplate('error.tpl');

        Logger::addLog("{$this->module->name} - Error while processing cart {$cart->id}: Failed to validate order", 3);

        return false;
    }
}
