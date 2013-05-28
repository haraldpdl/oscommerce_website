<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\Registry;

  class GetPartnerStatusUpdates {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');
      $OSCOM_Template = Registry::get('Template');

      $group = 'en';

      if ( isset($_GET['group']) && in_array($_GET['group'], array('de')) ) {
        $group = HTML::outputProtected($_GET['group']);
      }

      $Qpartners = $OSCOM_PDO->prepare('select p.title, p.url, su.status_update, c.title as category_title, c.code as category_code from :table_website_partner p, :table_website_partner_transaction t, :table_website_partner_status_update su, :table_website_partner_category c where t.package_id = 3 and t.date_start <= now() and t.date_end >= now() and t.partner_id = p.id and p.id = su.partner_id and su.code = :code and p.category_id = c.id group by p.id order by rand() limit 5');
      $Qpartners->bindValue(':code', $group);
      $Qpartners->setCache('website_partners-all-status_update-' . $group, 60);
      $Qpartners->execute();

      $result = array();

      while ( $Qpartners->fetch() ) {
        $result[] = array('title' => $Qpartners->valueProtected('title'),
                          'url' => $Qpartners->valueProtected('url'),
                          'status_update' => $OSCOM_Template->parseContent($Qpartners->valueProtected('status_update'), array('url')),
                          'category_title' => $Qpartners->value('category_title'),
                          'category_code' => $Qpartners->value('category_code'));
      }

      header('Cache-Control: max-age=3600, must-revalidate');
      header_remove('Pragma');
      header('Content-Type: application/javascript');

      echo json_encode($result);
    }
  }
?>
