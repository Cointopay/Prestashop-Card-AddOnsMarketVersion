{**
 * 2007-2024 PrestaShop and Contributors
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
 * @copyright  2010-2024 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<div class="panel" id="cointopayDirectCCForm">
    <div class="panel-heading">Payment Page({$smarty.get.displayName|escape:'htmlall':'UTF-8'})</div>
    <div>
        Payment Page For this Order:<br/>
        <span id="cointopayDirectCCURL">{$smarty.get.paymentUrl|escape:'htmlall':'UTF-8'}</span>
    </div>
    <form method="post" action="" style="display:inline-block;">
        <input type="submit" class="btn btn-outline-secondary" name="send{$smarty.get.name|escape:'htmlall':'UTF-8'}Payment"
               value="Send To Customer"/>
    </form>
    <button id="cointopayDirectCCCopyBtn" class="btn btn-outline-secondary"
            onclick="ctpDirectCopy()"
            style="margin-left:10px;">Copy URL to clipboard
    </button>
</div>
<script>
    function ctpDirectCopy() {
        const copyText = document.getElementById("cointopayDirectCCURL");
        navigator.clipboard.writeText(copyText.innerText);
    }
</script>