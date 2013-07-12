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

  class GetPartnerStatusUpdateUrlCode {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $id = null;

      $Qurl = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update_urls where partner_id = :partner_id and url = :url limit 1');
      $Qurl->bindInt(':partner_id', $data['partner_id']);
      $Qurl->bindValue(':url', $data['url']);
      $Qurl->execute();

      if ( $Qurl->fetch() !== false ) {
        $id = $Qurl->value('id');
      }

      while ( !isset($id) ) {
        $id = Hash::getRandomString(8);

        $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update_urls where id = :id');
        $Qcheck->bindValue(':id', $id);
        $Qcheck->execute();

        if ( $Qcheck->fetch() === false ) {
          $new_url = array('id' => $id,
                           'partner_id' => $data['partner_id'],
                           'url' => $data['url'],
                           'date_added' => 'now()');

          $OSCOM_PDO->save('website_partner_status_update_urls', $new_url);
        } else {
          $id = null;
        }
      }

      return $id;
    }
  }
?>
