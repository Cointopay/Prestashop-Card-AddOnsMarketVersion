<?php
/**
 * 2007-2025 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright  2010-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/cointopay_direct_cc_custom/vendor/cointopay/init.php';
require_once _PS_MODULE_DIR_ . '/cointopay_direct_cc_custom/vendor/version.php';

class Cointopay_Direct_Cc_CustomCallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        $order_id = explode('----', Tools::getValue('CustomerReferenceNr'))[1];
        $TransactionID = Tools::getValue('TransactionID');

        $ConfirmCode = Tools::getValue('ConfirmCode');

        $order = new Order($order_id);

        try {
            if (!$order) {
                $error_message = 'Cointopay Order #' . explode('----', Tools::getValue('CustomerReferenceNr'))[0] . ' does not exists';

                $this->logError($error_message, $order_id);
                throw new Exception($error_message);
            }

            $ctp_order_status = Tools::getValue('status');
            $ctp_order_status_notenough = Tools::getValue('notenough');
            $merchant_id = Configuration::get('COINTOPAY_DIRECT_CC_CUSTOM_MERCHANT_ID');
            $security_code = Configuration::get('COINTOPAY_DIRECT_CC_CUSTOM_SECURITY_CODE');
            $user_currency = Configuration::get('COINTOPAY_DIRECT_CC_CUSTOM_CRYPTO_CURRENCY');
            $selected_currency = !empty($user_currency) ? $user_currency : 1;
            $ctpConfig = [
                'merchant_id' => $merchant_id,
                'security_code' => $security_code,
                'selected_currency' => $selected_currency,
                'user_agent' => 'Cointopay - Prestashop v' . _PS_VERSION_ . ' Extension v' . COINTOPAY_DIRECT_CC_CUSTOM_PRESTASHOP_EXTENSION_VERSION,
            ];

            sleep(5);

            cointopay_direct_cc_custom\Cointopay_Direct_Cc_Custom::config($ctpConfig);
            $response_ctp = cointopay_direct_cc_custom\Merchant\Order::ValidateOrder([
                'TransactionID' => $TransactionID,
                'ConfirmCode' => $ConfirmCode,
            ]);

            if (isset($response_ctp)) {
                if (null != $response_ctp->data['Security'] && $response_ctp->data['Security'] != $ConfirmCode) {
                    $this->context->smarty->assign(['text' => $response_ctp->data->Security . 'Data mismatch! ConfirmCode doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != $response_ctp->data['CustomerReferenceNr'] && $response_ctp->data['CustomerReferenceNr'] != Tools::getValue('CustomerReferenceNr')) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! CustomerReferenceNr doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != $response_ctp->data['TransactionID'] && $response_ctp->data['TransactionID'] != $TransactionID) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! TransactionID doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != Tools::getValue('AltCoinID') && $response_ctp->data['AltCoinID'] != Tools::getValue('AltCoinID')) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! AltCoinID doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != Tools::getValue('COINTOPAY_DIRECT_CC_CUSTOM_MERCHANT_ID') && $response_ctp->data['MerchantID'] != Tools::getValue('COINTOPAY_DIRECT_CC_CUSTOM_MERCHANT_ID')) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! MerchantID doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != Tools::getValue('CoinAddressUsed') && $response_ctp->data['coinAddress'] != Tools::getValue('CoinAddressUsed')) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! coinAddress doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != Tools::getValue('SecurityCode') && $response_ctp->data['SecurityCode'] != Tools::getValue('SecurityCode')) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! SecurityCode doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif (null != Tools::getValue('inputCurrency') && $response_ctp->data['inputCurrency'] != Tools::getValue('inputCurrency')) {
                    $this->context->smarty->assign(['text' => 'Data mismatch! inputCurrency doesn\'t match']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } elseif ($response_ctp->data['Status'] != $ctp_order_status && $ctp_order_status_notenough == 0) {
                    $this->context->smarty->assign(['text' => 'We have detected different order status. Your order status is ' . $response_ctp->data['Status']]);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                } else {
                    if ($ctp_order_status == 'paid' && $ctp_order_status_notenough == 0) {
                        $order_status = 'PS_OS_PAYMENT';
                    } elseif ($ctp_order_status == 'paid' && $ctp_order_status_notenough == 1) {
                        $order_status = 'COINTOPAY_DIRECT_CC_PNOTENOUGH';
                        $this->logError('PS Orders is paid cointopay notenough', $order_id);
                    } elseif ($ctp_order_status == 'failed') {
                        $order_status = 'PS_OS_ERROR';
                        $this->logError('PS Orders is failed', $order_id);
                    } elseif ($ctp_order_status == 'underpaid') {
                        $order_status = 'COINTOPAY_DIRECT_CC_PNOTENOUGH';
                        $this->logError('PS Orders is paid cointopay notenough', $order_id);
                    } elseif ($ctp_order_status == 'expired') {
                        $order_status = 'COINTOPAY_DIRECT_CC_EXPIRED';
                        $this->logError('PS Orders is expired', $order_id);
                    } elseif ($ctp_order_status == 'canceled') {
                        $order_status = 'PS_OS_CANCELED';
                    } elseif ($ctp_order_status == 'waiting') {
                        $order_status = 'COINTOPAY_DIRECT_CC_WAITING';
                    } elseif ($ctp_order_status == 'refunded') {
                        $order_status = 'PS_OS_REFUND';
                    } else {
                        $order_status = false;
                    }

                    if ($order_status !== false && $order_status == 'PS_OS_PAYMENT') {
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState((int) Configuration::get($order_status), $order->id);
                        $history->addWithemail(
                            true,
                            ['order_name' => explode('----', Tools::getValue('CustomerReferenceNr'))[0]]
                        );
                        $this->context->smarty->assign(['text' => 'Successfully Paid Order #' . $order_id]);
                        if (_PS_VERSION_ >= '1.7') {
                            $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/ctp_payment_callback.tpl');
                        } else {
                            $this->setTemplate('ctp_payment_callback.tpl');
                        }
                    } elseif ($order_status == 'COINTOPAY_DIRECT_CC_PNOTENOUGH') {
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState((int) Configuration::get($order_status), $order->id);
                        $history->addWithemail(
                            true,
                            ['order_name' => explode('----', Tools::getValue('CustomerReferenceNr'))[0]]
                        );

                        $this->context->smarty->assign([
                            'text' => 'Please pay remaining amount for Order #' . $order_id,
                            'RedirectURL' => Tools::getValue('RedirectURL'),
                        ]);
                        if (_PS_VERSION_ >= '1.7') {
                            $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/ctp_payment_paidnotenough.tpl');
                        } else {
                            $this->setTemplate('ctp_payment_paidnotenough.tpl');
                        }
                    } elseif ($order_status == 'PS_OS_ERROR') {
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState((int) Configuration::get($order_status), $order->id);
                        $history->addWithemail(
                            true,
                            ['order_name' => explode('----', Tools::getValue('CustomerReferenceNr'))[0]]
                        );

                        $this->context->smarty->assign(['text' => 'Payment failed for Order #' . $order_id]);
                        if (_PS_VERSION_ >= '1.7') {
                            $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/ctp_payment_cancel.tpl');
                        } else {
                            $this->setTemplate('ctp_payment_cancel.tpl');
                        }
                    } elseif ($order_status == 'COINTOPAY_DIRECT_CC_EXPIRED') {
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState((int) Configuration::get($order_status), $order->id);
                        $history->addWithemail(
                            true,
                            ['order_name' => explode('----', Tools::getValue('CustomerReferenceNr'))[0]]
                        );

                        $this->context->smarty->assign(['text' => 'Payment expired for Order #' . $order_id]);
                        if (_PS_VERSION_ >= '1.7') {
                            $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/ctp_payment_callback.tpl');
                        } else {
                            $this->setTemplate('ctp_payment_callback.tpl');
                        }
                    } elseif ($order_status == 'PS_OS_REFUND') {
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState((int) Configuration::get($order_status), $order->id);
                        $history->addWithemail(
                            true,
                            ['order_name' => explode('----', Tools::getValue('CustomerReferenceNr'))[0]]
                        );

                        Tools::redirect($this->context->link->getModuleLink('cointopay_direct_cc_custom', 'cancel'));
                    } else {
                        $this->context->smarty->assign(['text' => 'Order Status ' . $ctp_order_status . ' not implemented']);
                        if (_PS_VERSION_ >= '1.7') {
                            $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/ctp_payment_callback.tpl');
                        } else {
                            $this->setTemplate('ctp_payment_callback.tpl');
                        }
                    }
                }
            } else {
                Tools::redirect($this->context->link->getPageLink('index', true) . 'order?step=3');
            }
        } catch (Exception $e) {
            $this->context->smarty->assign(['text' => get_class($e) . ': ' . $e->getMessage()]);
            if (_PS_VERSION_ >= '1.7') {
                $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/ctp_payment_cancel.tpl');
            } else {
                $this->setTemplate('ctp_payment_cancel.tpl');
            }
        }
    }

    private function logError($message, $cart_id)
    {
        PrestaShopLogger::addLog($message, 3, null, 'Cart', $cart_id, true);
    }
}
