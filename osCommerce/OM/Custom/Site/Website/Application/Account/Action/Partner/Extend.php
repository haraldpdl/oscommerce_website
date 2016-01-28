<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\HTML;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

use osCommerce\OM\Core\Site\Website\Partner;

class Extend
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['Extend']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Extend'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Extend']);

        if ((int)$partner_campaign['billing_country_id'] < 1) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_billing_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $OSCOM_Template->setValue('partner_campaign', $partner_campaign);
        $OSCOM_Template->setValue('partner_header', HTML::image(OSCOM::getPublicSiteLink(empty($partner_campaign['image_big']) ? $OSCOM_Template->getValue('highlights_image') : 'images/partners/' . $partner_campaign['image_big'])));

        $OSCOM_Template->setValue('paypal_server', OSCOM::getConfig('paypal_server'));
        $OSCOM_Template->setValue('paypal_merchant_id', OSCOM::getConfig('paypal_' . OSCOM::getConfig('paypal_server') . '_merchant_id'));

        $OSCOM_Template->setValue('payment_init_url', OSCOM::getRPCLink(null, null, 'PartnerExtendPayment&p=' . $partner_campaign['code'], 'SSL'));

        $application->setPageContent('partner_extend.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner_campaign['title']
        ]));

        $OSCOM_Template->addHtmlHeaderTag('<link rel="stylesheet" type="text/css" href="public/external/jquery/toastr/2.1.2/toastr.min.css" />');
        $OSCOM_Template->addHtmlHeaderTag('<script src="public/external/jquery/toastr/2.1.2/toastr.min.js"></script>');
        $OSCOM_Template->addHtmlHeaderTag('<script>
toastr.options = {
  escapeHtml: true,
  closeButton: true,
  positionClass: "toast-top-full-width",
  timeOut: 0,
  preventDuplicates: true
};
</script>');
    }
}
