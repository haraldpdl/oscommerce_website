<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class PartnerGetCampaigns {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qpartner = $OSCOM_PDO->prepare('select p.id, p.title, p.code, c.title as category_title, max(t.date_end) as date_end from :table_website_partner p left join :table_website_partner_transaction t on (p.id = t.partner_id), :table_website_partner_category c, :table_website_partner_account a where a.community_account_id = :community_account_id and a.partner_id = p.id and p.category_id = c.id group by t.partner_id');
      $Qpartner->bindInt(':community_account_id', $data['id']);
      $Qpartner->execute();

      return $Qpartner->fetchAll();
    }
  }
?>
