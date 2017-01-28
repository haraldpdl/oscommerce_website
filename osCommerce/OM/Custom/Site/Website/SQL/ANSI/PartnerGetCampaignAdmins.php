<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class PartnerGetCampaignAdmins
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qadmins = $OSCOM_PDO->prepare('select distinct a.community_account_id from :table_website_partner_account a, :table_website_partner_info pi where pi.code = :code and pi.partner_id = a.partner_id');
        $Qadmins->bindValue(':code', $data['code']);
        $Qadmins->execute();

        $admins = [];

        while ($Qadmins->fetch()) {
            $admins[] = $Qadmins->valueInt('community_account_id');
        }

        return $admins;
    }
}
