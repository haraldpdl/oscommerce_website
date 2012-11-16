<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class SaveUserServerInfo {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_user_server_info where submit_ip = :submit_ip limit 1');
      $Qcheck->bindValue(':submit_ip', $data['ip_address']);
      $Qcheck->execute();

      if ( count($Qcheck->fetchAll()) > 0 ) {
        $Qrecord = $OSCOM_PDO->prepare('update :table_website_user_server_info set osc_version = :osc_version, system_os = :system_os, http_server = :http_server, php_version = :php_version, php_extensions = :php_extensions, php_sapi = :php_sapi, php_memory = :php_memory, mysql_version = :mysql_version, php_other = :php_other, system_other = :system_other, mysql_other = :mysql_other, date_updated = now(), update_count = update_count+1 where submit_ip = :submit_ip');
      } else {
        $Qrecord = $OSCOM_PDO->prepare('insert into :table_website_user_server_info values (null, :submit_ip, :osc_version, :system_os, :http_server, :php_version, :php_extensions, :php_sapi, :php_memory, :mysql_version, :php_other, :system_other, :mysql_other, now(), now(), 1)');
      }

      $Qrecord->bindValue(':submit_ip', $data['ip_address']);
      $Qrecord->bindValue(':osc_version', $data['osc_version']);
      $Qrecord->bindValue(':system_os', $data['system_os']);
      $Qrecord->bindValue(':http_server', $data['http_server']);
      $Qrecord->bindValue(':php_version', $data['php_version']);
      $Qrecord->bindValue(':php_extensions', $data['php_extensions']);
      $Qrecord->bindValue(':php_sapi', $data['php_sapi']);
      $Qrecord->bindValue(':php_memory', $data['php_memory']);
      $Qrecord->bindValue(':mysql_version', $data['mysql_version']);
      $Qrecord->bindValue(':php_other', $data['php_other']);
      $Qrecord->bindValue(':mysql_other', $data['mysql_other']);
      $Qrecord->bindValue(':system_other', $data['system_other']);
      $Qrecord->execute();

      return $Qrecord->rowCount();
    }
  }
?>
