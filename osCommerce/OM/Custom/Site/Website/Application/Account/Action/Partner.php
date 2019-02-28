<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner as PartnerClass;

class Partner
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $req_actions = http_build_query(array_slice($_GET, array_search('Partner', array_keys($_GET))));

            $_SESSION['login_redirect'] = [
                'url' => OSCOM::getLink(null, null, $req_actions, 'SSL')
            ];

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
        }

        if (PartnerClass::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'])) {
            $OSCOM_Template->setValue('partner_campaigns', PartnerClass::getCampaigns($_SESSION[OSCOM::getSite()]['Account']['id']));
            $OSCOM_Template->setValue('partner_date_now', date('Y-m-d H:i:s'));

            $application->setPageContent('partner.html');
        } else {
            $application->setPageContent('partner_empty.html');
        }

        $application->setPageTitle(OSCOM::getDef('partner_html_title'));

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights/services.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/highlights/services.jpg', true);
        } else {
            $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif', true);
        }
    }
}
