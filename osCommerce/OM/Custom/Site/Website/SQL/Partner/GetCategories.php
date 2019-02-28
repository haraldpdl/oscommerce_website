<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetCategories
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = <<<EOD
select
  coalesce(lang_user.title, lang_en.title) as title,
  c.code
from
  :table_website_partner_category c
    left join
      :table_website_partner_category_lang lang_user
        on
          (c.id = lang_user.id and lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_category_lang lang_en
        on
          (c.id = lang_en.id and lang_en.languages_id = :default_language_id),
  :table_website_partner_transaction t,
  :table_website_partner p
    left join
      :table_website_partner_info pi_lang_user
        on
          (p.id = pi_lang_user.partner_id and pi_lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_info pi_lang_en
        on
          (p.id = pi_lang_en.partner_id and pi_lang_en.languages_id = :default_language_id),
  :table_website_partner_package pp
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
  c.id
order by
  c.sort_order,
  title
EOD;
        } else {
            $sql = <<<EOD
select
  cl.title,
  c.code
from
  :table_website_partner_category c,
  :table_website_partner_category_lang cl,
  :table_website_partner_transaction t,
  :table_website_partner p,
  :table_website_partner_info pi,
  :table_website_partner_package pp
where
  t.date_start <= now() and
  t.date_end >= now() and
  t.package_id = pp.id and
  pp.status = 1 and
  t.partner_id = p.id and
  p.category_id = c.id and
  c.id = cl.id and
  cl.languages_id = :default_language_id and
  p.id = pi.partner_id and
  pi.languages_id = cl.languages_id and
  pi.image_small != '' and
  pi.desc_short != '' and
  pi.desc_long != ''
group by
  c.id
order by
  c.sort_order,
  cl.title
EOD;
        }

        $Qgroups = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qgroups->bindInt(':languages_id', $data['language_id']);
        }

        $Qgroups->bindInt(':default_language_id', $data['default_language_id']);
        $Qgroups->setCache('website_partner_categories-lang' . ($data['language_id'] ?? $data['default_language_id']), 720);
        $Qgroups->execute();

        return $Qgroups->fetchAll();
    }
}
