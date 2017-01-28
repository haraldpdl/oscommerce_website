<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class PartnerGetCampaign
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $sql = <<<EOD
select
  p.*,
  (
    select
      sum(timestampdiff(month, t2.date_start, t2.date_end))
    from
      :table_website_partner_transaction t2
    where
      p.id = t2.partner_id and
      t2.date_end >= now()
  ) as total_duration,
  country.countries_iso_code_2 as billing_country_iso_code_2,
  c.code as category_code,
  c.title as category_title,
  max(t.date_end) as date_end,
  t.package_id,
  if (t.package_id = 3, 1, 0) as has_gold,
  pkg.code as pkg_code
from
  :table_website_partner p
    left join
      :table_countries country
        on
          (p.billing_country_id = country.countries_id),
  :table_website_partner_info pi,
  :table_website_partner_category c,
  :table_website_partner_transaction t,
  :table_website_partner_package pkg,
  :table_website_partner_account a
where
  a.community_account_id = :community_account_id and
  a.partner_id = p.id and
  p.id = pi.partner_id and
  pi.code = :code and
  p.category_id = c.id and
  p.id = t.partner_id and
  t.package_id = pkg.id
EOD;

        $Qpartner = $OSCOM_PDO->prepare($sql);
        $Qpartner->bindInt(':community_account_id', $data['id']);
        $Qpartner->bindValue(':code', $data['code']);
        $Qpartner->execute();

        return $Qpartner->fetch();
    }
}
