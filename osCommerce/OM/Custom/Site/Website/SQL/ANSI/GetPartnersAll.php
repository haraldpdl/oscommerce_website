<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetPartnersAll
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = <<<EOD
select
  coalesce(pi_lang_user.title, pi_lang_en.title) as title,
  coalesce(pi_lang_user.code, pi_lang_en.code) as code,
  coalesce(pi_lang_user.desc_short, pi_lang_en.desc_short) as desc_short,
  coalesce(pi_lang_user.image_small, pi_lang_en.image_small) as image_small,
  c.code as category_code
from
  :table_website_partner p
    left join
      :table_website_partner_info pi_lang_user
        on
          (p.id = pi_lang_user.partner_id and pi_lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_info pi_lang_en
        on
          (p.id = pi_lang_en.partner_id and pi_lang_en.languages_id = :default_language_id),
  :table_website_partner_transaction t,
  :table_website_partner_package pp,
  :table_website_partner_category c
where
  t.date_start <= now() and
  t.date_end >= now() and
  t.package_id = pp.id and
  pp.status = 1 and
  t.partner_id = p.id and
  p.category_id = c.id and
  coalesce(pi_lang_user.image_small, pi_lang_en.image_small) != '' and
  coalesce(pi_lang_user.desc_short, pi_lang_en.desc_short) != '' and
  coalesce(pi_lang_user.desc_long, pi_lang_en.desc_long) != ''
group by
  p.id
order by
  sum(t.cost) desc,
  title
EOD;
        } else {
            $sql = <<<EOD
select
  pi.title,
  pi.code,
  pi.desc_short,
  pi.image_small,
  c.code as category_code
from
  :table_website_partner p,
  :table_website_partner_info pi,
  :table_website_partner_transaction t,
  :table_website_partner_package pp,
  :table_website_partner_category c
where
  t.date_start <= now() and
  t.date_end >= now() and
  t.package_id = pp.id and
  pp.status = 1 and
  t.partner_id = p.id and
  p.category_id = c.id and
  p.id = pi.partner_id and
  pi.languages_id = :default_language_id and
  pi.image_small != '' and
  pi.desc_short != '' and
  pi.desc_long != ''
group by
  p.id
order by
  sum(t.cost) desc,
  pi.title
EOD;
        }

        $Qpartners = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qpartners->bindInt(':languages_id', $data['language_id']);
        }

        $Qpartners->bindInt(':default_language_id', $data['default_language_id']);
        $Qpartners->setCache('website_partners-all-lang' . ($data['language_id'] ?? $data['default_language_id']), 720);
        $Qpartners->execute();

        return $Qpartners->fetchAll();
    }
}
