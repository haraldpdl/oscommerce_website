<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class GetPartner {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qpartner = $OSCOM_PDO->prepare('select p.*, c.countries_iso_code_2 as billing_country_iso_code_2 from :table_website_partner p left join :table_countries c on p.billing_country_id = c.countries_id where p.code = :code');
      $Qpartner->bindValue(':code', $data['code']);
      $Qpartner->setCache('website_partner-' . $data['code']);
      $Qpartner->execute();

      return $Qpartner->fetch();
    }
  }
?>
