<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Tag;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class partnerurl extends \osCommerce\OM\Core\Template\TagAbstract
{
    protected static $_parse_result = false;

    public static function execute($string)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $args = func_get_args();
        $code = trim($args[1]);

        $params = explode('|', $string, 2);

        if (empty($params[0])) {
            return (isset($params[1]) && !empty($params[1]) ? $params[1] : '');
        }

        $url = $params[0];

        $new_url = $OSCOM_PDO->call('Site\\Website\\GetPartnerStatusUpdateUrlCode', ['partner_id' => Partner::get($code, 'id'), 'url' => $url]);

        return '<a href="' . OSCOM::getLink('Website', 'Services', 'Redirect=' . $code . '&url=' . $new_url, 'SSL', false) . '" target="_blank">' . (isset($params[1]) ? $params[1] : $params[0]) . '</a>';
    }
}
