<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\AuditLog;
  use osCommerce\OM\Core\Cache;
  use osCommerce\OM\Core\OSCOM;

  use osCommerce\OM\Core\Site\Website\Users;

  class Partner {
    protected static $_partner;
    protected static $_partners;
    protected static $_categories;
    protected static $_promotions;

    public static function get($code, $key = null) {
      if ( !isset(static::$_partner[$code]) ) {
        static::$_partner[$code] = OSCOM::callDB('Website\GetPartner', array('code' => $code), 'Site');
      }

      return isset($key) ? static::$_partner[$code][$key] : static::$_partner[$code];
    }

    public static function getAll() {
      return OSCOM::callDB('Website\GetPartnersAll', null, 'Site');
    }

    public static function getInCategory($code) {
      if ( !isset(static::$_partners[$code]) ) {
        static::$_partners[$code] = OSCOM::callDB('Website\GetPartners', array('code' => $code), 'Site');
      }

      return static::$_partners[$code];
    }

    public static function exists($code, $category) {
      if ( !isset(static::$_partners[$category]) ) {
        static::$_partners[$category] = OSCOM::callDB('Website\GetPartners', array('code' => $category), 'Site');
      }

      foreach ( static::$_partners[$category] as $p ) {
        if ( $p['code'] == $code ) {
          return true;
        }
      }

      return false;
    }

    public static function getCategory($code, $key = null) {
      if ( !isset(static::$_categories) ) {
        static::getCategories();
      }

      foreach ( static::$_categories as $c ) {
        if ( $c['code'] == $code ) {
          return isset($key) ? $c[$key] : $c;
        }
      }

      return false;
    }

    public static function getCategories() {
      if ( !isset(static::$_categories) ) {
        static::$_categories = OSCOM::callDB('Website\GetPartnerCategories', null, 'Site');
      }

      return static::$_categories;
    }

    public static function categoryExists($code) {
      if ( !isset(static::$_categories) ) {
        static::getCategories();
      }

      foreach ( static::$_categories as $c ) {
        if ( $c['code'] == $code ) {
          return true;
        }
      }

      return false;
    }

    public static function getPromotions() {
      if ( !isset(static::$_promotions) ) {
        static::$_promotions = OSCOM::callDB('Website\GetPartnerPromotions', null, 'Site');
      }

      return static::$_promotions;
    }

    public static function hasCampaign($id, $code = null) {
      $data = array('id' => $id);

      if ( isset($code) ) {
        $data['code'] = $code;
      }

      return OSCOM::callDB('Website\PartnerHasCampaign', $data, 'Site');
    }

    public static function getCampaigns($id) {
      return OSCOM::callDB('Website\PartnerGetCampaigns', array('id' => $id), 'Site');
    }

    public static function getCampaign($id, $code) {
      return OSCOM::callDB('Website\PartnerGetCampaign', array('id' => $id, 'code' => $code), 'Site');
    }

    public static function getCampaignAdmins($code) {
      return OSCOM::callDB('Website\PartnerGetCampaignAdmins', array('code' => $code), 'Site');
    }

    public static function getStatusUpdateUrl($code, $url_id) {
      return OSCOM::callDB('Website\GetPartnerStatusUpdateUrl', array('partner_id' => static::get($code, 'id'), 'id' => $url_id), 'Site');
    }

    public static function getAudit($code) {
      $OSCOM_Cache = new Cache();

      $id = $code;

      if ( !is_numeric($id) ) {
        $id = static::get($id, 'id');
      }

      if ( $OSCOM_Cache->read('website_partner-' . $code . '-audit') ) {
        $result = $OSCOM_Cache->getCache();
      } else {
        $result = AuditLog::getAll('Website\Account\Partner', $id, 6);

        foreach ( $result as &$record ) {
          $record['user_name'] = Users::get($record['user_id'], 'name');
          $record['date_added'] = (new \DateTime($record['date_added']))->format('jS M Y H:i');
        }

        $OSCOM_Cache->write($result);
      }

      return $result;
    }

    public static function save($user_id, $code, $partner) {
      $campaign = static::getCampaign($user_id, $code);

      $data = array('id' => $campaign['id'],
                    'code' => $code,
                    'desc_short' => $partner['desc_short'],
                    'desc_long' => $partner['desc_long'],
                    'address' => isset($partner['address']) ? $partner['address'] : null,
                    'telephone' => isset($partner['telephone']) ? $partner['telephone'] : null,
                    'email' => isset($partner['email']) ? $partner['email'] : null,
                    'youtube_video_id' => isset($partner['youtube_video_id']) ? $partner['youtube_video_id'] : null,
                    'url' => $partner['url'],
                    'public_url' => $partner['public_url'],
                    'image_small' => isset($partner['image_small']) ? $partner['image_small'] : null,
                    'image_big' => (($campaign['has_gold'] == '1') && isset($partner['image_big'])) ? $partner['image_big'] : null,
                    'image_promo' => null,
                    'image_promo_url' => null,
                    'banner_image_en' => null,
                    'banner_url_en' => null,
                    'status_update_en' => null,
                    'banner_image_de' => null,
                    'banner_url_de' => null,
                    'status_update_de' => null);

      if ( $campaign['has_gold'] == '1' ) {
        if ( isset($partner['image_promo_url']) ) {
          $data['image_promo_url'] = $partner['image_promo_url'];

          if ( isset($partner['image_promo']) ) {
            $data['image_promo'] = $partner['image_promo'];
          }
        }

        if ( isset($partner['banner_url_en']) ) {
          $data['banner_url_en'] = $partner['banner_url_en'];

          if ( isset($partner['banner_image_en']) ) {
            $data['banner_image_en'] = $partner['banner_image_en'];
          }
        }

        if ( isset($partner['status_update_en']) ) {
          $data['status_update_en'] = $partner['status_update_en'];
        }

        if ( isset($partner['banner_url_de']) ) {
          $data['banner_url_de'] = $partner['banner_url_de'];

          if ( isset($partner['banner_image_de']) ) {
            $data['banner_image_de'] = $partner['banner_image_de'];
          }
        }

        if ( isset($partner['status_update_de']) ) {
          $data['status_update_de'] = $partner['status_update_de'];
        }
      }

      if ( OSCOM::callDB('Website\PartnerSave', $data, 'Site') ) {
        static::auditLog($campaign, $data);

        Cache::clear('website_partner-' . $data['code']);
        Cache::clear('website_partner_promotions');
        Cache::clear('website_partners');

        return true;
      }

      return false;
    }

    protected static function auditLog($orig, $new) {
// file uploads are not always performed so non-uploaded file fields are removed for array_diff_assoc()
      if ( $new['image_small'] === null ) {
        unset($new['image_small']);
      }

      if ( $new['image_big'] === null ) {
        unset($new['image_big']);
      }

      if ( $new['image_promo'] === null ) {
        unset($new['image_promo']);
      }

      if ( $new['banner_image_en'] === null ) {
        unset($new['banner_image_en']);
      }

      if ( $new['banner_image_de'] === null ) {
        unset($new['banner_image_de']);
      }

      $diff = array_diff_assoc($new, $orig);

// new file uploads may share the same name as existing files so they are added manually to the array diff
      if ( isset($new['image_small']) && ($new['image_small'] == $orig['image_small']) ) {
        $diff['image_small'] = $new['image_small'];
      }

      if ( isset($new['image_big']) && ($new['image_big'] == $orig['image_big']) ) {
        $diff['image_big'] = $new['image_big'];
      }

      if ( isset($new['image_promo']) && ($new['image_promo'] == $orig['image_promo']) ) {
        $diff['image_promo'] = $new['image_promo'];
      }

      if ( isset($new['banner_image_en']) && ($new['banner_image_en'] == $orig['banner_image_en']) ) {
        $diff['banner_image_en'] = $new['banner_image_en'];
      }

      if ( isset($new['banner_image_de']) && ($new['banner_image_de'] == $orig['banner_image_de']) ) {
        $diff['banner_image_de'] = $new['banner_image_de'];
      }

      if ( !empty($diff) ) {
        $data = [ 'action' => 'Partner',
                  'id' => $orig['id'],
                  'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'],
                  'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
                  'action_type' => 'update',
                  'rows' => [ ] ];

        foreach ( $diff as $key => $new_value ) {
          $data['rows'][] = [ 'key' => $key,
                              'old' => isset($orig[$key]) ? $orig[$key] : null,
                              'new' => $new_value ];
        }

        AuditLog::save($data);
      }
    }
  }
?>
