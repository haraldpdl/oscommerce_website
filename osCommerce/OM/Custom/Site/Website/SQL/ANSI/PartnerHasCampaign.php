<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class PartnerHasCampaign
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['code'])) {
            $sql = <<<EOD
select
  a.community_account_id
from
  :table_website_partner_account a,
  :table_website_partner_info pi
where
  a.community_account_id = :community_account_id and
  a.partner_id = pi.partner_id and
  pi.code = :code
limit
  1
EOD;
        } else {
            $sql = <<<EOD
select
  community_account_id
from
  :table_website_partner_account
where
  community_account_id = :community_account_id
limit
  1
EOD;
        }

        $Qcheck = $OSCOM_PDO->prepare($sql);
        $Qcheck->bindInt(':community_account_id', $data['id']);

        if (isset($data['code'])) {
            $Qcheck->bindValue(':code', $data['code']);
        }

        $Qcheck->execute();

        return $Qcheck->fetch() !== false;
    }
}
