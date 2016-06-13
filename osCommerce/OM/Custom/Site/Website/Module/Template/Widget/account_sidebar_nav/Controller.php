<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\account_sidebar_nav;

use osCommerce\OM\Core\{
  OSCOM,
  Registry
};

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    public static function execute($param = null)
    {
        $OSCOM_Application = Registry::get('Application');
        $OSCOM_Template = Registry::get('Template');

        $req = $OSCOM_Application->getRequestedActions();

        if ((count($req) > 1) && ($req[0] == 'Partner')) {
            $OSCOM_Template->setValue('sidebar_nav_extra_link', [
                'url' => OSCOM::getLink(null, 'Account', 'Partner', 'SSL'),
                'title' => OSCOM::getDef('partner_back_to_campaigns')
            ]);
        }

        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/account_sidebar_nav/pages/main.html';

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/account_sidebar_nav/pages/main.html';
        }

        return file_get_contents($file);
    }
}
