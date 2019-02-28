<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetCampaignInfo
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $sql = <<<EOD
select
  code,
  title,
  desc_short,
  desc_long,
  address,
  telephone,
  email,
  url,
  public_url,
  image_small,
  image_big,
  image_promo,
  image_promo_url,
  youtube_video_id,
  carousel_image,
  carousel_title,
  carousel_url,
  banner_image,
  banner_url,
  status_update
from
  :table_website_partner_info
where
  partner_id = :partner_id and
  languages_id = :languages_id
EOD;

        $Qpartner = $OSCOM_PDO->prepare($sql);
        $Qpartner->bindInt(':partner_id', $data['id']);
        $Qpartner->bindInt(':languages_id', $data['language_id']);
        $Qpartner->execute();

        return $Qpartner->fetch();
    }
}
