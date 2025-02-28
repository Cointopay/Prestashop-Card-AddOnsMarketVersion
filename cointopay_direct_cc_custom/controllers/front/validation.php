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
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/cointopay_direct_cc_custom/vendor/cointopay/init.php';
require_once _PS_MODULE_DIR_ . '/cointopay_direct_cc_custom/vendor/version.php';

class Cointopay_direct_cc_customValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect($this->context->link->getPageLink('index', true) . 'order?step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'cointopay_direct_cc_custom') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            exit($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink('index', true) . 'order?step=1');
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $this->module->validateOrder($cart->id, Configuration::get('COINTOPAY_DIRECT_CC_WAITING'), $total, $this->module->displayName, null, [], (int) $currency->id, false, $customer->secure_key);
        $link = new Link();
        $success_url = '';
        $success_url = $link->getPageLink(
            'order-confirmation',
            null,
            null,
            [
                'id_cart' => $cart->id,
                'id_module' => $this->module->id,
                'key' => $customer->secure_key,
            ]
        );
        $description = [];
        foreach ($cart->getProducts() as $product) {
            $description[] = $product['cart_quantity'] . ' × ' . $product['name'];
        }
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
        $orderObj = new Order($this->module->currentOrder);

        cointopay_direct_cc_custom\Cointopay_Direct_Cc_Custom::config($ctpConfig);
        $order = cointopay_direct_cc_custom\Merchant\Order::createOrFail([
            'order_id' => implode('----', [$orderObj->reference, $this->module->currentOrder]),
            'price' => $total,
            'currency' => $this->currencyCode($currency->iso_code),
            'cancel_url' => $this->flashEncode($this->context->link->getModuleLink('cointopay_direct_cc_custom', 'cancel')),
            'callback_url' => $this->flashEncode($this->context->link->getModuleLink('cointopay_direct_cc_custom', 'callback')),
            'success_url' => $success_url,
            'title' => Configuration::get('PS_SHOP_NAME') . ' Order #' . $orderObj->reference,
            'description' => implode(', ', $description),
            'selected_currency' => $selected_currency,
        ]);

        if (isset($order)) {
            file_put_contents('ctpresp.log', $order->PaymentDetailCConly);

            // create new DOMDocument
            $htmlDom = new \DOMDocument('1.0', 'UTF-8');

            // set error level
            $internalErrors = libxml_use_internal_errors(true);
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

    /**
     * Currency code
     * @param $isoCode
     * @return string
     */
    public function currencyCode($isoCode)
    {
        $currencyCode = '';
        if (isset($isoCode) && ($isoCode == 'RUB')) {
            $currencyCode = 'RUR';
        } else {
            $currencyCode = $isoCode;
        }
        return $currencyCode;
    }
}
