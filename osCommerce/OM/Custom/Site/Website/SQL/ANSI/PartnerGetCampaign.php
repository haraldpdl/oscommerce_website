<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class PartnerGetCampaign {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qpartner = $OSCOM_PDO->prepare('select p.*, c.code as category_code, c.title as category_title, max(t.date_end) as date_end, t.package_id, if (t.package_id = 3, 1, 0) as has_gold, b1.image as banner_image_en, b1.url as banner_url_en, b1.twitter as twitter_en, b2.image as banner_image_de, b2.url as banner_url_de, b2.twitter as twitter_de, su1.status_update as status_update_en, su2.status_update as status_update_de from :table_website_partner p left join :table_website_partner_banner b1 on (p.id = b1.partner_id and b1.code = "en") left join :table_website_partner_banner b2 on (p.id = b2.partner_id and b2.code = "de") left join :table_website_partner_status_update su1 on (p.id = su1.partner_id and su1.code = "en") left join :table_website_partner_status_update su2 on (p.id = su2.partner_id and su2.code = "de"), :table_website_partner_category c, :table_website_partner_transaction t, :table_website_partner_account a where a.community_account_id = :community_account_id and a.partner_id = p.id and p.code = :code and p.category_id = c.id and p.id = t.partner_id');
      $Qpartner->bindInt(':community_account_id', $data['id']);
      $Qpartner->bindValue(':code', $data['code']);
      $Qpartner->execute();

      return $Qpartner->fetch();
    }
  }
?>
