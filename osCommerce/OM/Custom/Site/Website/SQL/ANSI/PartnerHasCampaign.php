<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class PartnerHasCampaign {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      if ( isset($data['code']) ) {
        $Qcheck = $OSCOM_PDO->prepare('select a.community_account_id from :table_website_partner_account a, :table_website_partner p where a.community_account_id = :community_account_id and a.partner_id = p.id and p.code = :code limit 1');
        $Qcheck->bindInt(':community_account_id', $data['id']);
        $Qcheck->bindValue(':code', $data['code']);
        $Qcheck->execute();
      } else {
        $Qcheck = $OSCOM_PDO->prepare('select community_account_id from :table_website_partner_account where community_account_id = :community_account_id limit 1');
        $Qcheck->bindInt(':community_account_id', $data['id']);
        $Qcheck->execute();
      }

      return $Qcheck->fetch() !== false;
    }
  }
?>
