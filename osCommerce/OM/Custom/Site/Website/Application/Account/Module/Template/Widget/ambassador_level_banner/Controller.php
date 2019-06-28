<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Module\Template\Widget\ambassador_level_banner;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    public static function execute($param = null)
    {
        $OSCOM_Template = Registry::get('Template');

        $user = $OSCOM_Template->getValue('user');

        if ($user['amb_level'] > 0) {
            $filename = 'main.html';
        } else {
            $filename = 'new.html';
        }

        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/ambassador_level_banner/pages/' . $filename;

        if (!is_file($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/ambassador_level_banner/pages/' . $filename;
        }

        return file_get_contents($file);
    }
}
