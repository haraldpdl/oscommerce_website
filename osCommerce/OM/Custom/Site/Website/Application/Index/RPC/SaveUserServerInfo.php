<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class SaveUserServerInfo {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');

      $result = '';

      if ( isset($_POST['info']) && !empty($_POST['info']) ) {
        $info = unserialize(base64_decode($_POST['info']));

        if ( is_array($info) && isset($info['oscommerce']['version']) && isset($info['system']['os']) && isset($info['system']['http_server'])  && isset($info['php']['version'])  && isset($info['php']['extensions']) && isset($info['php']['sapi']) && isset($info['php']['memory_limit']) && isset($info['mysql']['version']) ) {
          $data = array('osc_version' => $info['oscommerce']['version'],
                        'system_os' => $info['system']['os'],
                        'http_server' => $info['system']['http_server'],
                        'php_version' => $info['php']['version'],
                        'php_extensions' => implode(',', $info['php']['extensions']),
                        'php_sapi' => $info['php']['sapi'],
                        'php_memory' => $info['php']['memory_limit'],
                        'mysql_version' => $info['mysql']['version']);

          unset($info['php']['version']);
          unset($info['php']['sapi']);
          unset($info['php']['memory_limit']);
          unset($info['php']['extensions']);
          unset($info['mysql']['version']);
          unset($info['system']['os']);
          unset($info['system']['host']);
          unset($info['system']['http_server']);

          $data['php_other'] = static::formatArray($info['php']);
          $data['mysql_other'] = static::formatArray($info['mysql']);
          $data['system_other'] = static::formatArray($info['system']);

          $data['ip_address'] = sha1(OSCOM::getIPAddress() . OSCOM::getConfig('save_user_server_info_salt', 'Website'));

          if ( OSCOM::callDB('Website\SaveUserServerInfo', $data, 'Site') === 1 ) {
            $result = 'OK';
          }
        }
      }

      echo $result;
    }

    public static function formatArray($array) {
      $output = '';

      foreach ( $array as $key => $value ) {
        $output .= $key . ' = ' . $value . "\n";
      }

      return trim($output);
    }
  }
?>
