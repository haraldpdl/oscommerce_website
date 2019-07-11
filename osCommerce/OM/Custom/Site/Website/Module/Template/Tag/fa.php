<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Tag;

use osCommerce\OM\Core\OSCOM;

class fa extends \osCommerce\OM\Core\Template\TagAbstract
{
    protected static $_parse_result = false;

    public static function execute($string)
    {
        $params = explode('|', $string, 3);

        if (mb_strpos($params[0], '/') === false) {
            $params[0] = 'solid/' . $params[0];
        }

        $params[1] = 'svg-inject' . (isset($params[1]) && !empty($params[1]) ? ' ' . $params[1] : '');

        $params[2] = isset($params[2]) ? ' style="' . $params[2] . '"' : '';

        return '<img src="' . OSCOM::getPublicSiteLink('external/fontawesome/svgs/' . $params[0] . '.svg') . '" class="' . $params[1] . '"' . $params[2] . ' onload="SVGInject(this)">';
    }
}
