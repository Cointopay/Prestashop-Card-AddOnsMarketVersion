<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2025 PrestaShop SA
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Core\Configuration\Configuration;
use PrestaShop\PrestaShop\Core\Utility\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_ROOT_DIR_ . '/config/config.inc.php';
require_once _PS_MODULE_DIR_ . '/cointopay_direct_cc_custom/vendor/cointopay/init.php';
require_once _PS_MODULE_DIR_ . '/cointopay_direct_cc_custom/vendor/version.php';

class Cointopay_Direct_Cc_CustomMakepaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $internal_order_id = Tools::getValue('internal_order_id');
        if (!empty($internal_order_id)) {
            $this->generatePayment($internal_order_id);
        } else {
            exit('Invalid Order ID.');
        }
    }

    public function generatePayment($internal_order_id)
    {
        $merchant_id = Configuration::get('COINTOPAY_DIRECT_CC_CUSTOM_MERCHANT_ID');
        $security_code = Configuration::get('COINTOPAY_DIRECT_CC_CUSTOM_SECURITY_CODE');
        $user_currency = Configuration::get('COINTOPAY_DIRECT_CC_CUSTOM_CRYPTO_CURRENCY');
        $selected_currency = !empty($user_currency) ? $user_currency : 1;
        $total = (float) Tools::getValue('amount');
        $ctpConfig = [
            'merchant_id' => $merchant_id,
            'security_code' => $security_code,
            'selected_currency' => $selected_currency,
            'user_agent' => 'Cointopay - Prestashop v' . _PS_VERSION_ . ' Extension v' . COINTOPAY_DIRECT_CC_CUSTOM_PRESTASHOP_EXTENSION_VERSION,
        ];

        cointopay_direct_cc_custom\Cointopay_Direct_Cc_Custom::config($ctpConfig);
        $order = cointopay_direct_cc_custom\Merchant\Order::createOrFail([
            'order_id' => implode('----', [Tools::getValue('id_order'), $internal_order_id]),
            'price' => $total,
            'currency' => Tools::getValue('isocode'),
            'cancel_url' => $this->flashEncode($this->context->link->getModuleLink('cointopay_direct_cc_custom', 'cancel')),
            'callback_url' => $this->flashEncode($this->context->link->getModuleLink('cointopay_direct_cc_custom', 'callback')),
            'title' => Configuration::get('PS_SHOP_NAME') . ' Order #' . $internal_order_id,
            'selected_currency' => $selected_currency,
        ]);

        if (isset($order)) {
            $htmlDom = new DOMDocument();
            $htmlDom->loadHTML($order->PaymentDetailCConly);
            $links = $htmlDom->getElementsByTagName('a');
            $matches = [];

            foreach ($links as $link) {
                $linkHref = $link->getAttribute('href');
                if (strlen(trim($linkHref)) == 0) {
                    continue;
                }
                if ($linkHref[0] == '#') {
                    continue;
                }
                $matches[] = $linkHref;
            }
            if (!empty($matches)) {
                if ($matches[0] != '') {
                    Tools::redirect($matches[0]);

                    exit;
                } else {
                    $this->context->smarty->assign(['text' => 'Payment link is empty']);
                    if (_PS_VERSION_ >= '1.7') {
                        $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                    } else {
                        $this->setTemplate('cointopay_payment_cancel.tpl');
                    }
                }
            } else {
                $this->context->smarty->assign(['text' => 'pattern not match']);
                if (_PS_VERSION_ >= '1.7') {
                    $this->setTemplate('module:cointopay_direct_cc_custom/views/templates/front/cointopay_payment_cancel.tpl');
                } else {
                    $this->setTemplate('cointopay_payment_cancel.tpl');
                }
            }
        } else {
            Tools::redirect($this->context->link->getPageLink('index', true) . 'order?step=3');
        }
        exit;
    }

    /**
     * URL encode to UTF-8
     *
     * @param $input
     * @return string
     */
    public function flashEncode($input)
    {
        return rawurlencode(mb_convert_encoding($input, 'UTF-8', mb_list_encodings()));
    }
}
