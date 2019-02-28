<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetCampaigns
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = <<<EOD
select
  p.id,
  coalesce(pi_lang_user.title, pi_lang_en.title) as title,
  coalesce(pi_lang_user.code, pi_lang_en.code) as code,
  coalesce(c_lang_user.title, c_lang_en.title) as category_title,
  max(t.date_end) as date_end
from
  :table_website_partner p
    left join
      :table_website_partner_info pi_lang_user
        on
          (p.id = pi_lang_user.partner_id and pi_lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_info pi_lang_en
        on
          (p.id = pi_lang_en.partner_id and pi_lang_en.languages_id = :default_language_id)
    left join
      :table_website_partner_transaction t
        on
          (p.id = t.partner_id),
  :table_website_partner_category c
    left join
      :table_website_partner_category_lang c_lang_user
        on
          (c.id = c_lang_user.id and c_lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_category_lang c_lang_en
        on
          (c.id = c_lang_en.id and c_lang_en.languages_id = :default_language_id),
  :table_website_partner_account a
where
  a.community_account_id = :community_account_id and
  a.partner_id = p.id and
  p.category_id = c.id
group by
  t.partner_id
order by
  date_end,
  title
EOD;
        } else {
            $sql = <<<EOD
select
  p.id,
  pi.title,
  pi.code,
  cl.title as category_title,
  max(t.date_end) as date_end
from
  :table_website_partner p
    left join
      :table_website_partner_transaction t
        on
          (p.id = t.partner_id),
  :table_website_partner_info pi,
  :table_website_partner_category_lang cl,
  :table_website_partner_account a
where
  a.community_account_id = :community_account_id and
  a.partner_id = p.id and
  p.category_id = cl.id and
  cl.languages_id = :default_language_id and
  p.id = pi.partner_id and
  pi.languages_id = :default_language_id
group by
  t.partner_id
order by
  date_end,
  pi.title
EOD;
        }

        $Qpartner = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qpartner->bindInt(':languages_id', $data['language_id']);
        }

        $Qpartner->bindInt(':community_account_id', $data['id']);
        $Qpartner->bindInt(':default_language_id', $data['default_language_id']);
        $Qpartner->execute();

        return $Qpartner->fetchAll();
    }
}
