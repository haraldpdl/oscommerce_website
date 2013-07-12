<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Hash;
  use osCommerce\OM\Core\Registry;

  class GetPartnerStatusUpdateUrl {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qurl = $OSCOM_PDO->prepare('select url from :table_website_partner_status_update_urls where id = :id and partner_id = :partner_id');
      $Qurl->bindValue(':id', $data['id']);
      $Qurl->bindInt(':partner_id', $data['partner_id']);
      $Qurl->execute();

      if ( $Qurl->fetch() !== false ) {
        return $Qurl->value('url');
      }

      return false;
    }
  }
?>
