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

use FirstAtlanticCommerceModule\FACTransaction;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/autoload.php';

/**
 * Class FirstAtlanticCommerce
 */
class FirstAtlanticCommerce extends PaymentModule
{
    const STATUS_VALIDATED = 'FACE_STAT_VALIDATED';
    const GO_LIVE = 'FAC_GO_LIVE';

    const PAGE_SET_NAME_LIVE = 'FAC_PAGE_SET_NAME_LIVE';
    const PAGE_NAME_LIVE = 'FAC_PAGE_NAME_LIVE';

    const MERCHANT_ID_LIVE = 'FAC_MERCHANT_ID_LIVE';
    const MERCHANT_PASSWORD_LIVE = 'FAC_MERCHANT_PASSWORD_LIVE';

    const PAGE_SET_NAME_TEST = 'FAC_PAGE_SET_NAME_TEST';
    const PAGE_NAME_TEST = 'FAC_PAGE_NAME_TEST';

    const MERCHANT_ID_TEST = 'FAC_MERCHANT_ID_TEST';
    const MERCHANT_PASSWORD_TEST = 'FAC_MERCHANT_PASSWORD_TEST';

    /** @var array Supported zero-decimal currencies */
    public static $zeroDecimalCurrencies = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'vdn', 'vuv', 'xaf', 'xof', 'xpf'];
    /** @var string $baseUrl Module base URL */
    public $baseUrl;
    public $moduleUrl;
    /** @var array Hooks */
    public $hooks = [
        'displayHeader',
        'displayPayment',
        'displayPaymentEU',
        'paymentReturn',
        'displayAdminOrder',
    ];

    /** @var int $menu Current menu */
    public $menu;

    /**
     * FirstAtlanticCommerce constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'firstatlanticcommerce';
        $this->tab = 'payments_gateways';
        $this->version = '0.7.2';
        $this->author = 'thirty bees';
        $this->need_instance = 1;

        $this->bootstrap = true;

        $this->controllers = ['confirmation', 'payment'];

        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('First Atlantic Commerce');
        $this->description = $this->l('Accept payments with First Atlantic Commerce (hosted pages)');

        $this->tb_versions_compliancy = '~1.0.0';
    }

    /**
     * Install the module
     *
     * @return bool Whether the module has been successfully installed
     *
     * @since 1.0.0
     */
    public function install()
    {
        if (!parent::install()) {
            parent::uninstall();

            return false;
        }

        FACTransaction::createDatabase();

        foreach ($this->hooks as $hook) {
            $this->registerHook($hook);
        }

        Configuration::updateGlobalValue(static::STATUS_VALIDATED, Configuration::get('PS_OS_PAYMENT'));

        return true;
    }

    /**
     * Uninstall the module
     *
     * @return bool Whether the module has been successfully installed
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        foreach ($this->hooks as $hook) {
            $this->unregisterHook($hook);
        }

        Configuration::deleteByName(static::STATUS_VALIDATED);

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return string HTML
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        $output = '';

        $this->moduleUrl = Context::getContext()->link->getAdminLink('AdminModules', false).'&token='.Tools::getAdminTokenLite('AdminModules').'&'.http_build_query([
            'configure' => $this->name,
        ]);

        $this->baseUrl = $this->context->link->getAdminLink('AdminModules', true).'&'.http_build_query([
            'configure'   => $this->name,
            'tab_module'  => $this->tab,
            'module_name' => $this->name,
        ]);

        $this->postProcess();

        return $output.$this->renderSettingsPage();
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitOptionsconfiguration') || Tools::isSubmit('submitOptionsconfiguration')) {
            $this->postProcessOrderOptions();
        }
    }

    /**
     * Process Order Options
     *
     * @return void
     */
    protected function postProcessOrderOptions()
    {
        $statusValidated = (int) Tools::getValue(static::STATUS_VALIDATED);

        $merchantIdLive = Tools::getValue(static::MERCHANT_ID_LIVE);
        $merchantPasswordLive = Tools::getValue(static::MERCHANT_PASSWORD_LIVE);
        $pageSetNameLive = Tools::getValue(static::PAGE_SET_NAME_LIVE);
        $pageNameLive = Tools::getValue(static::PAGE_NAME_LIVE);

        $merchantIdTest = Tools::getValue(static::MERCHANT_ID_TEST);
        $merchantPasswordTest = Tools::getValue(static::MERCHANT_PASSWORD_TEST);
        $pageSetNameTest = Tools::getValue(static::PAGE_SET_NAME_TEST);
        $pageNameTest = Tools::getValue(static::PAGE_NAME_TEST);

        $goLive = (bool) Tools::getValue(static::GO_LIVE);

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                $this->updateAllValue(static::STATUS_VALIDATED, $statusValidated);
                $this->updateAllValue(static::MERCHANT_ID_LIVE, $merchantIdLive);
                $this->updateAllValue(static::MERCHANT_PASSWORD_LIVE, $merchantPasswordLive);
                $this->updateAllValue(static::PAGE_SET_NAME_LIVE, $pageSetNameLive);
                $this->updateAllValue(static::PAGE_NAME_LIVE, $pageNameLive);
                $this->updateAllValue(static::MERCHANT_ID_TEST, $merchantIdTest);
                $this->updateAllValue(static::MERCHANT_PASSWORD_TEST, $merchantPasswordTest);
                $this->updateAllValue(static::PAGE_SET_NAME_TEST, $pageSetNameTest);
                $this->updateAllValue(static::PAGE_NAME_TEST, $pageNameTest);
                $this->updateAllValue(static::GO_LIVE, $goLive);
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $idShopGroup = (int) Shop::getGroupFromShop($this->getShopId(), true);
                $multishopOverride = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    foreach (Shop::getShops(false, $this->getShopId()) as $idShop) {
                        if (isset($multishopOverride[static::STATUS_VALIDATED]) && $multishopOverride[static::STATUS_VALIDATED]) {
                            Configuration::updateValue(static::STATUS_VALIDATED, $statusValidated, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::MERCHANT_ID_TEST]) && $multishopOverride[static::MERCHANT_ID_TEST]) {
                            Configuration::updateValue(static::MERCHANT_ID_TEST, $merchantIdTest, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::MERCHANT_PASSWORD_TEST]) && $multishopOverride[static::MERCHANT_PASSWORD_TEST]) {
                            Configuration::updateValue(static::MERCHANT_PASSWORD_TEST, $merchantPasswordTest, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::PAGE_SET_NAME_TEST]) && $multishopOverride[static::PAGE_SET_NAME_TEST]) {
                            Configuration::updateValue(static::PAGE_SET_NAME_TEST, $pageSetNameTest, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::PAGE_NAME_TEST]) && $multishopOverride[static::PAGE_NAME_TEST]) {
                            Configuration::updateValue(static::PAGE_NAME_TEST, $pageNameTest, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::MERCHANT_ID_LIVE]) && $multishopOverride[static::MERCHANT_ID_LIVE]) {
                            Configuration::updateValue(static::MERCHANT_ID_LIVE, $merchantIdLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::MERCHANT_PASSWORD_LIVE]) && $multishopOverride[static::MERCHANT_PASSWORD_LIVE]) {
                            Configuration::updateValue(static::MERCHANT_PASSWORD_LIVE, $merchantPasswordLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::PAGE_SET_NAME_LIVE]) && $multishopOverride[static::PAGE_SET_NAME_LIVE]) {
                            Configuration::updateValue(static::PAGE_SET_NAME_LIVE, $pageSetNameLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::PAGE_NAME_LIVE]) && $multishopOverride[static::PAGE_NAME_LIVE]) {
                            Configuration::updateValue(static::PAGE_NAME_LIVE, $pageNameLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::GO_LIVE]) && $multishopOverride[static::GO_LIVE]) {
                            Configuration::updateValue(static::GO_LIVE, $goLive, false, $idShopGroup, $idShop);
                        }
                    }
                } else {
                    $idShop = (int) $this->getShopId();
                    if (isset($multishopOverride[static::STATUS_VALIDATED]) && $multishopOverride[static::STATUS_VALIDATED]) {
                        Configuration::updateValue(static::STATUS_VALIDATED, $statusValidated, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::MERCHANT_ID_TEST]) && $multishopOverride[static::MERCHANT_ID_TEST]) {
                        Configuration::updateValue(static::MERCHANT_ID_TEST, $merchantIdTest, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::MERCHANT_PASSWORD_TEST]) && $multishopOverride[static::MERCHANT_PASSWORD_TEST]) {
                        Configuration::updateValue(static::MERCHANT_PASSWORD_TEST, $merchantPasswordTest, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::PAGE_SET_NAME_TEST]) && $multishopOverride[static::PAGE_SET_NAME_TEST]) {
                        Configuration::updateValue(static::PAGE_SET_NAME_TEST, $pageSetNameTest, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::PAGE_NAME_TEST]) && $multishopOverride[static::PAGE_NAME_TEST]) {
                        Configuration::updateValue(static::PAGE_NAME_TEST, $pageNameTest, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::MERCHANT_ID_LIVE]) && $multishopOverride[static::MERCHANT_ID_LIVE]) {
                        Configuration::updateValue(static::MERCHANT_ID_LIVE, $merchantIdLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::MERCHANT_PASSWORD_LIVE]) && $multishopOverride[static::MERCHANT_PASSWORD_LIVE]) {
                        Configuration::updateValue(static::MERCHANT_PASSWORD_LIVE, $merchantPasswordLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::PAGE_SET_NAME_LIVE]) && $multishopOverride[static::PAGE_SET_NAME_LIVE]) {
                        Configuration::updateValue(static::PAGE_SET_NAME_LIVE, $pageSetNameLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::PAGE_NAME_LIVE]) && $multishopOverride[static::PAGE_NAME_LIVE]) {
                        Configuration::updateValue(static::PAGE_NAME_LIVE, $pageNameLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::GO_LIVE]) && $multishopOverride[static::GO_LIVE]) {
                        Configuration::updateValue(static::GO_LIVE, $goLive, false, $idShopGroup, $idShop);
                    }
                }
            }
        }

        Configuration::updateValue(static::STATUS_VALIDATED, $statusValidated);
        Configuration::updateValue(static::MERCHANT_ID_LIVE, $merchantPasswordLive);
        Configuration::updateValue(static::MERCHANT_PASSWORD_LIVE, $merchantPasswordLive);
        Configuration::updateValue(static::PAGE_SET_NAME_LIVE, $pageSetNameLive);
        Configuration::updateValue(static::PAGE_NAME_LIVE, $pageNameLive);
        Configuration::updateValue(static::MERCHANT_ID_TEST, $merchantIdTest);
        Configuration::updateValue(static::MERCHANT_PASSWORD_TEST, $merchantPasswordTest);
        Configuration::updateValue(static::PAGE_SET_NAME_TEST, $pageSetNameTest);
        Configuration::updateValue(static::PAGE_NAME_TEST, $pageNameTest);
        Configuration::updateValue(static::GO_LIVE, $goLive);
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key    Configuration key
     * @param mixed  $values Configuration values, can be string or array with id_lang as key
     * @param bool   $html   Contains HTML
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * Get the Shop ID of the current context
     * Retrieves the Shop ID from the cookie
     *
     * @return int Shop ID
     */
    public function getShopId()
    {
        if (isset(Context::getContext()->employee->id) && Context::getContext()->employee->id && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $cookie = Context::getContext()->cookie->getFamily('shopContext');

            return (int) Tools::substr($cookie['shopContext'], 2, count($cookie['shopContext']));
        }

        return (int) Context::getContext()->shop->id;
    }

    /**
     * Render the general settings page
     *
     * @return string HTML
     * @throws Exception
     * @throws SmartyException
     *
     * @since 1.0.0
     */
    protected function renderSettingsPage()
    {
        $output = '';

        $this->context->smarty->assign(
            [
                'module_url' => $this->moduleUrl,
                'baseUrl'    => $this->baseUrl,
            ]
        );

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        $output .= $this->renderGeneralOptions();

        return $output;
    }

    /**
     * Render the General options form
     *
     * @return string HTML
     *
     * @since 1.0.0
     */
    protected function renderGeneralOptions()
    {
        $helper = new HelperOptions();
        $helper->id = 1;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $helper->table = 'configuration';
        $helper->show_toolbar = false;

        return $helper->generateOptions(
            array_merge(
                $this->getOrderOptions()
            )
        );
    }

    /**
     * Get available options for orders
     *
     * @return array Order options
     *
     * @since 1.0.0
     */
    protected function getOrderOptions()
    {
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);

        $statusValidated = (int) Configuration::get(static::STATUS_VALIDATED);
        if ($statusValidated < 1) {
            $statusValidated = (int) Configuration::get('PS_OS_PAYMENT');
        }

        return [
            'orders' => [
                'title'  => $this->l('Order Settings'),
                'icon'   => 'icon-credit-card',
                'fields' => [
                    static::GO_LIVE    => [
                        'title'      => $this->l('Go live'),
                        'type'       => 'bool',
                        'name'       => static::GO_LIVE,
                        'value'      => Configuration::get(static::GO_LIVE),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::MERCHANT_ID_TEST       => [
                        'title'      => $this->l('Test Merchant ID'),
                        'type'       => 'text',
                        'name'       => static::MERCHANT_ID_TEST,
                        'value'      => Configuration::get(static::MERCHANT_ID_TEST),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::MERCHANT_PASSWORD_TEST => [
                        'title'      => $this->l('Test Merchant password'),
                        'type'       => 'text',
                        'name'       => static::MERCHANT_PASSWORD_TEST,
                        'value'      => Configuration::get(static::MERCHANT_PASSWORD_TEST),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::PAGE_SET_NAME_TEST     => [
                        'title'      => $this->l('Test Page set name'),
                        'type'       => 'text',
                        'name'       => static::PAGE_SET_NAME_TEST,
                        'value'      => Configuration::get(static::PAGE_SET_NAME_TEST),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::PAGE_NAME_TEST     => [
                        'title'      => $this->l('Test Page name'),
                        'type'       => 'text',
                        'name'       => static::PAGE_NAME_TEST,
                        'value'      => Configuration::get(static::PAGE_NAME_TEST),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::MERCHANT_ID_LIVE       => [
                        'title'      => $this->l('Live Merchant ID'),
                        'type'       => 'text',
                        'name'       => static::MERCHANT_ID_LIVE,
                        'value'      => Configuration::get(static::MERCHANT_ID_LIVE),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::MERCHANT_PASSWORD_LIVE => [
                        'title'      => $this->l('Live Merchant password'),
                        'type'       => 'text',
                        'name'       => static::MERCHANT_PASSWORD_LIVE,
                        'value'      => Configuration::get(static::MERCHANT_PASSWORD_LIVE),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::PAGE_SET_NAME_LIVE     => [
                        'title'      => $this->l('Live Page set name'),
                        'type'       => 'text',
                        'name'       => static::PAGE_SET_NAME_LIVE,
                        'value'      => Configuration::get(static::PAGE_SET_NAME_LIVE),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::PAGE_NAME_LIVE     => [
                        'title'      => $this->l('Live Page name'),
                        'type'       => 'text',
                        'name'       => static::PAGE_NAME_LIVE,
                        'value'      => Configuration::get(static::PAGE_NAME_LIVE),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                        'size'       => 64,
                    ],
                    static::STATUS_VALIDATED       => [
                        'title'      => $this->l('Payment accepted status'),
                        'des'        => $this->l('Order status to use when the payment is accepted'),
                        'type'       => 'select',
                        'list'       => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name'       => static::STATUS_VALIDATED,
                        'value'      => $statusValidated,
                        'validation' => 'isString',
                        'cast'       => 'strval',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     *
     * @return string
     */
    public function hookPayment()
    {
        if (!$this->active
            || (Configuration::get(static::GO_LIVE) && !Configuration::get(static::MERCHANT_ID_LIVE) && !Configuration::get(static::MERCHANT_PASSWORD_LIVE) && !Configuration::get(static::PAGE_SET_NAME_LIVE) && !Configuration::get(static::PAGE_NAME_LIVE))
            || (!Configuration::get(static::GO_LIVE) && !Configuration::get(static::MERCHANT_ID_TEST) && !Configuration::get(static::MERCHANT_PASSWORD_TEST) && !Configuration::get(static::PAGE_SET_NAME_TEST) && !Configuration::get(static::PAGE_NAME_TEST))
        ) {
            return '';
        }

        $this->context->smarty->assign([
            'fac_payment_page' => $this->context->link->getModuleLink($this->name, 'payment', [], Tools::usingSecureMode()),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Hook to Advanced EU checkout
     *
     * @return array|bool Smarty variables, nothing if should not be shown
     */
    public function hookDisplayPaymentEU()
    {
        /** @var Cart $cart */
        if (!$this->active || (!Configuration::get(static::MERCHANT_ID_LIVE) && !Configuration::get(static::MERCHANT_PASSWORD_LIVE) && !Configuration::get(static::PAGE_SET_NAME_LIVE) && !Configuration::get(static::PAGE_NAME_LIVE))) {
            return false;
        }

        $paymentOptions = [
            'cta_text' => $this->l('Pay by Credit Card'),
            'logo'     => Media::getMediaPath($this->local_path.'views/img/creditcardlogos.jpg'),
            'action'   => $this->context->link->getModuleLink($this->name, 'payment', [], Tools::usingSecureMode()),
        ];

        return $paymentOptions;
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param array $params Hook parameters
     *
     * @return string Hook HTML
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        /** @var Order $order */
        $order = $params['objOrder'];

        $currency = new Currency($order->id_currency);

        if (isset($order->reference) && $order->reference) {
            $totalToPay = (float) $order->getTotalPaid($currency);
            $reference = $order->reference;
        } else {
            $totalToPay = $order->total_paid_tax_incl;
            $reference = $this->l('Unknown');
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->context->smarty->assign('status', 'ok');
        }

        $this->context->smarty->assign(
            [
                'id_order'  => $order->id,
                'reference' => $reference,
                'params'    => $params,
                'total'     => Tools::displayPrice($totalToPay, $currency, false),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/confirmation.tpl');
    }

    /**
     * Display on Back Office order page
     *
     * @return string Hook HTML
     * @throws Exception
     * @throws SmartyException
     */
    public function hookDisplayAdminOrder($params)
    {
        if (!isset($params['id_order'])) {
            return '';
        }

        $transaction = FACTransaction::getByIdOrder((int) $params['id_order']);
        if (!Validate::isLoadedObject($transaction)) {
            return '';
        }

        $this->context->smarty->assign('transaction', $transaction);

        return $this->display(__FILE__, 'views/templates/admin/adminorder.tpl');
    }

    /**
     * Detect Back Office settings
     *
     * @return array Array with error message strings
     */
    protected function detectBOSettingsErrors()
    {
        $langId = Context::getContext()->language->id;
        $output = [];
        if (Configuration::get('PS_DISABLE_NON_NATIVE_MODULE')) {
            $output[] = $this->l('Non native modules such as this one are disabled. Go to').' "'.
                $this->getTabName('AdminParentPreferences', $langId).
                ' > '.
                $this->getTabName('AdminPerformance', $langId).
                '" '.$this->l('and make sure that the option').' "'.
                Translate::getAdminTranslation('Disable non PrestaShop modules', 'AdminPerformance').
                '" '.$this->l('is set to').' "'.
                Translate::getAdminTranslation('No', 'AdminPerformance').
                '"'.$this->l('.').'<br />';
        }

        return $output;
    }

    /**
     * Get Tab name from database
     *
     * @param $className string Class name of tab
     * @param $idLang    int Language id
     *
     * @return string Returns the localized tab name
     */
    protected function getTabName($className, $idLang)
    {
        if ($className == null || $idLang == null) {
            return '';
        }

        $sql = new DbQuery();
        $sql->select('tl.`name`');
        $sql->from('tab_lang', 'tl');
        $sql->innerJoin('tab', 't', 't.`id_tab` = tl.`id_tab`');
        $sql->where('t.`class_name` = \''.pSQL($className).'\'');
        $sql->where('tl.`id_lang` = '.(int) $idLang);

        try {
            return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        } catch (Exception $e) {
            return $this->l('Unknown');
        }
    }

    /**
     * Add information message
     *
     * @param string $message Message
     * @param bool   $private
     */
    protected function addInformation($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->informations[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->informations[] = $message;
        }
    }

    /**
     * Add confirmation message
     *
     * @param string $message Message
     * @param bool   $private
     */
    protected function addConfirmation($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->confirmations[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->confirmations[] = $message;
        }
    }

    /**
     * Add warning message
     *
     * @param string $message Message
     * @param bool   $private
     */
    protected function addWarning($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->warnings[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->warnings[] = $message;
        }
    }

    /**
     * Add error message
     *
     * @param string $message Message
     */
    protected function addError($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->errors[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            // Do not add error in this case
            // It will break execution of AdminController
            $this->context->controller->warnings[] = $message;
        }
    }
}
