<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\OSCOM;

  class Download {
    protected static $_files;

    public static function getAll($pkg_group = null, $rel_group = null) {
      if ( !isset(static::$_files) ) {
        static::setReleases();
      }

      if ( isset($pkg_group) ) {
        if ( isset($rel_group) ) {
          $rel = [ ];

          foreach ( static::$_files[$pkg_group] as $code => $data ) {
            if ( $data['group'] == $rel_group ) {
              $rel[$code] = $data;
            }
          }

          return $rel;
        }

        return static::$_files[$pkg_group];
      }

      return static::$_files;
    }

    public static function get($id, $key = null) {
      if ( !isset(static::$_files) ) {
        static::setReleases();
      }

      if ( !is_numeric($id) ) {
        $id = static::getID($id);
      }

      foreach ( static::$_files as $pkg_group => $releases ) {
        foreach ( $releases as $code => $data ) {
          if ( $data['id'] == $id ) {
            if ( isset($key) ) {
              return $data[$key];
            } else {
              return $data;
            }
          }
        }
      }

      return false;
    }

    public static function getID($code) {
      if ( !isset(static::$_files) ) {
        static::setReleases();
      }

      foreach ( static::$_files as $pkg_group => $releases ) {
        foreach ( $releases as $file_code => $file_data ) {
          if ( $code == $file_code ) {
            return $file_data['id'];
          }
        }
      }

      return false;
    }

    public static function exists($id) {
      if ( !isset(static::$_files) ) {
        static::setReleases();
      }

      if ( !is_numeric($id) ) {
        $id = static::getID($id);
      }

      foreach ( static::$_files as $pkg_group => $releases ) {
        foreach ( $releases as $code => $data ) {
          if ( $data['id'] == $id ) {
            return true;
          }
        }
      }

      return false;
    }

    public static function incrementDownloadCounter($id) {
      if ( !is_numeric($id) ) {
        $id = static::getID($id);
      }

      return OSCOM::callDB('Website\IncrementDownloadCounter', array('id' => $id), 'Site');
    }

    public static function logDownload($id) {
      if ( !is_numeric($id) ) {
        $id = static::getID($id);
      }

      return OSCOM::callDB('Website\LogDownload', array('id' => $id, 'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))), 'Site');
    }

    protected static function setReleases() {
      static::$_files = OSCOM::callDB('Website\GetReleases', null, 'Site');
    }
  }
?>
