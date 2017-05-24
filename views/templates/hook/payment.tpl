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

<div class="row">
    <div class="col-xs-12 col-md-12">
        <p class="payment_module" id="fac_payment_button">
            <a id="fac_payment_link" href="{$fac_payment_page|escape:'htmlall':'UTf-8'}" title="{l s='Pay by Credit Card' mod='firstatlanticcommerce'}">
                <img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/creditcardlogos.jpg" height="64px" width="auto" alt="{l s='Credit cards' mod='firstatlanticcommerce'}"/>
                {l s='Pay with Credit Card' mod='firstatlanticcommerce'}
            </a>
        </p>
    </div>
</div>
