{*
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
*}
<div class="panel">
    <div class="panel-heading"><i class="icon icon-credit-card"></i> First Atlantic Commerce - transaction</div>
    <h4>{l s='Transaction information' mod='firstatlanticcommerce'}</h4>
    <p><strong>{l s='Reference' mod='firstatlanticcommerce'}:</strong> {$transaction->reference|escape:'htmlall':'UTF-8'}<br/>
    <strong>{l s='Response Code' mod='firstatlanticcommerce'}:</strong> {$transaction->response_code|intval}<br/>
    <strong>{l s='Reason Code' mod='firstatlanticcommerce'}:</strong> {$transaction->reason_code|intval}<br/>
    <strong>{l s='Reason Description' mod='firstatlanticcommerce'}:</strong> {$transaction->reason_desc|escape:'htmlall':'UTF-8'}<br/>
    <strong>{l s='Card Number' mod='firstatlanticcommerce'}:</strong> {$transaction->card_number|escape:'htmlall':'UTF-8'}<br/>
    <strong>{l s='CVV2 Result' mod='firstatlanticcommerce'}:</strong> {$transaction->cvv_result|escape:'htmlall':'UTF-8'}<br/>
    <strong>{l s='Merchant ID' mod='firstatlanticcommerce'}:</strong> {$transaction->merchant_id|escape:'htmlall':'UTF-8'}<br/>
    <strong>{l s='Order Number' mod='firstatlanticcommerce'}:</strong> {$transaction->order_number|escape:'htmlall':'UTF-8'}</p>
    {if isset($transaction->fraud_control_id) && $transaction->fraud_control_id}
        <h4>{l s='Fraud Control' mod='firstatlanticcommerce'}</h4>
        <p><strong>{l s='Fraud Control ID' mod='firstatlanticcommerce'}:</strong> {$transaction->fraud_control_id|escape:'htmlall':'UTF-8'}<br/>
        <strong>{l s='Fraud Response Code' mod='firstatlanticcommerce'}:</strong> {$transaction->fraud_response_code|escape:'htmlall':'UTF-8'}<br/>
        <strong>{l s='Reason Code' mod='firstatlanticcommerce'}:</strong> {$transaction->fraud_reason_code|escape:'htmlall':'UTF-8'}<br/>
        <strong>{l s='Reason Description' mod='firstatlanticcommerce'}:</strong> {$transaction->fraud_reason_desc|escape:'htmlall':'UTF-8'}<br/>
        <strong>{l s='Fraud Score' mod='firstatlanticcommerce'}:</strong> {$transaction->fraud_score|escape:'htmlall':'UTF-8'}</p>
    {/if}
</div>
