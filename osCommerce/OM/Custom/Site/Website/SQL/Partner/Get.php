<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class Get
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = <<<EOD
select
  p.id,
  p.app_code,
  p.date_last_updated,
  coalesce(pi_lang_user.code, pi_lang_en.code) as code,
  coalesce(pi_lang_user.title, pi_lang_en.title) as title,
  coalesce(pi_lang_user.desc_short, pi_lang_en.desc_short) as desc_short,
  coalesce(pi_lang_user.desc_long, pi_lang_en.desc_long) as desc_long,
  coalesce(pi_lang_user.address, pi_lang_en.address) as address,
  coalesce(pi_lang_user.telephone, pi_lang_en.telephone) as telephone,
  coalesce(pi_lang_user.email, pi_lang_en.email) as email,
  coalesce(pi_lang_user.url, pi_lang_en.url) as url,
  coalesce(pi_lang_user.public_url, pi_lang_en.public_url) as public_url,
  coalesce(pi_lang_user.image_small, pi_lang_en.image_small) as image_small,
  coalesce(pi_lang_user.image_big, pi_lang_en.image_big) as image_big,
  coalesce(pi_lang_user.image_promo, pi_lang_en.image_promo) as image_promo,
  coalesce(pi_lang_user.image_promo_url, pi_lang_en.image_promo_url) as image_promo_url,
  coalesce(pi_lang_user.youtube_video_id, pi_lang_en.youtube_video_id) as youtube_video_id,
  coalesce(pi_lang_user.carousel_image, pi_lang_en.carousel_image) as carousel_image,
  coalesce(pi_lang_user.carousel_title, pi_lang_en.carousel_title) as carousel_title,
  coalesce(pi_lang_user.carousel_url, pi_lang_en.carousel_url) as carousel_url,
  c.countries_iso_code_2 as billing_country_iso_code_2,
  cat.code as category_code
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
      :table_countries c
        on
          (p.billing_country_id = c.countries_id),
  :table_website_partner_info pi,
  :table_website_partner_category cat
where
  p.id = pi.partner_id and
  pi.code = :code and
  p.category_id = cat.id
EOD;
        } else {
            $sql = <<<EOD
select
  p.id,
  p.app_code,
  p.date_last_updated,
  pi.code,
  pi.title,
  pi.desc_short,
  pi.desc_long,
  pi.address,
  pi.telephone,
  pi.email,
  pi.url,
  pi.public_url,
  pi.image_small,
  pi.image_big,
  pi.image_promo,
  pi.image_promo_url,
  pi.youtube_video_id,
  pi.carousel_image,
  pi.carousel_title,
  pi.carousel_url,
  c.countries_iso_code_2 as billing_country_iso_code_2,
  cat.code as category_code
from
  :table_website_partner p
    left join
      :table_countries c
        on
          (p.billing_country_id = c.countries_id),
  :table_website_partner_info pi,
  :table_website_partner_category cat
where
  p.id = pi.partner_id and
  pi.code = :code and
  pi.languages_id = :default_language_id and
  p.category_id = cat.id
EOD;
        }

        $Qpartner = $OSCOM_PDO->prepare($sql);
        $Qpartner->bindValue(':code', $data['code']);

        if (isset($data['language_id'])) {
            $Qpartner->bindInt(':languages_id', $data['language_id']);
        }

        $Qpartner->bindInt(':default_language_id', $data['default_language_id']);
        $Qpartner->setCache('website_partner-' . $data['code'] . '-lang' . ($data['language_id'] ?? $data['default_language_id']));
        $Qpartner->execute();

        return $Qpartner->fetch();
    }
}
