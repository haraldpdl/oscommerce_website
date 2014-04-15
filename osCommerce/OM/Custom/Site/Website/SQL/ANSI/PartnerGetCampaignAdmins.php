<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class PartnerGetCampaignAdmins {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qadmins = $OSCOM_PDO->prepare('select a.community_account_id from :table_website_partner_account a, :table_website_partner p where p.code = :code and p.id = a.partner_id');
      $Qadmins->bindValue(':code', $data['code']);
      $Qadmins->execute();

      $admins = array();

      while ( $Qadmins->fetch() ) {
        $admins[] = $Qadmins->valueInt('community_account_id');
      }

      return $admins;
    }
  }
?>
