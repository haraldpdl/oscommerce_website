<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Services\Module\Template\Widget\services_sidebar_nav;

use osCommerce\OM\Core\OSCOM;

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    public static function execute($param = null)
    {
        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/services_sidebar_nav/pages/main.html';

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/services_sidebar_nav/pages/main.html';
        }

        return file_get_contents($file);
    }
}
