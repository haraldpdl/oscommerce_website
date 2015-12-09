<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\partner_edit_sidebar_nav;

use osCommerce\OM\Core\OSCOM;

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    static public function execute($param = null)
    {
        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/partner_edit_sidebar_nav/pages/main.html';

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/partner_edit_sidebar_nav/pages/main.html';
        }

        return file_get_contents($file);
    }
}
