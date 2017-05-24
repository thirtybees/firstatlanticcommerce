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

namespace FirstAtlanticCommerceModule;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class FACTransaction
 */
class FACTransaction extends \ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_order */
    public $id_order;
    /** @var string $reference */
    public $reference;
    /** @var int $response_code */
    public $response_code;
    /** @var int $reason_code */
    public $reason_code;
    /** @var string $reason_desc */
    public $reason_desc;
    /** @var string $card_number */
    public $card_number;
    /** @var string $cvv_result */
    public $cvv_result;
    /** @var string $merchant_id */
    public $merchant_id;
    /** @var string $order_number */
    public $order_number;
    /** @var int $purchase_amount */
    public $purchase_amount;
    /** @var string $fraud_control_id */
    public $fraud_control_id;
    /** @var string $fraud_response_code */
    public $fraud_response_code;
    /** @var string $fraud_reason_code */
    public $fraud_reason_code;
    /** @var string $fraud_reason_desc */
    public $fraud_reason_desc;
    /** @var string $fraud_score */
    public $fraud_score;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'fac_transaction',
        'primary' => 'id_fac_transaction',
        'fields' => [
            'id_order'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',               'required' => true, 'default' => '0', 'db_type' => 'INT(11) UNSIGNED'],
            'reference'           => ['type' => self::TYPE_STRING, 'validate' => 'isString',                   'required' => true, 'default' => '0', 'db_type' => 'INT(11) UNSIGNED'],
            'response_code'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt',              'required' => true, 'default' => '0', 'db_type' => 'INT(11) UNSIGNED'],
            'reason_code'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'size' => 4, 'required' => true, 'default' => '0', 'db_type' => 'INT(4) UNSIGNED'],
            'reason_desc'         => ['type' => self::TYPE_STRING, 'validate' => 'isString',                   'required' => true,                   'db_type' => 'TEXT'],
            'card_number'         => ['type' => self::TYPE_STRING, 'validate' => 'isString',                   'required' => true, 'default' => '0', 'db_type' => 'CHAR(16)'],
            'cvv_result'          => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'merchant_id'         => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'order_number'        => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'purchase_amount'     => ['type' => self::TYPE_INT,    'validate' => 'isInt',                                                            'db_type' => 'INT(11)'],
            'fraud_control_id'    => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'fraud_response_code' => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'fraud_reason_code'   => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'fraud_reason_desc'   => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'fraud_score'         => ['type' => self::TYPE_STRING, 'validate' => 'isString',                                                         'db_type' => 'VARCHAR(255)'],
            'date_add'            => ['type' => self::TYPE_DATE,   'validate' => 'isString',                                                         'db_type' => 'DATETIME'],
            'date_upd'            => ['type' => self::TYPE_DATE,   'validate' => 'isString',                                                         'db_type' => 'DATETIME'],
        ],
    ];

    /**
     * Get FACTransaction by ID Order
     *
     * @param int $idOrder
     *
     * @return false|FACTransaction
     *
     * @since 1.0.0
     */
    public static function getByIdOrder($idOrder)
    {
        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_order` = '.(int) $idOrder);

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if ($result) {
            $transaction = new static();
            $transaction->hydrate($result);

            return $transaction;
        }

        return false;
    }
}
