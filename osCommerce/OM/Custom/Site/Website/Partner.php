<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\AuditLog;
  use osCommerce\OM\Core\Cache;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Users;

  class Partner {
    protected static $_partner;
    protected static $_partners;
    protected static $_categories;
    protected static $_promotions;
    protected static $packages;

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

    public static function exists($code, $category = null) {
      if (isset($category)) {
        if ( !isset(static::$_partners[$category]) ) {
          static::$_partners[$category] = OSCOM::callDB('Website\GetPartners', array('code' => $category), 'Site');
        }

        foreach ( static::$_partners[$category] as $p ) {
          if ( $p['code'] == $code ) {
            return true;
          }
        }
      } else {
        $partner = static::get($code);

        return is_array($partner) && !empty($partner);
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

    public static function getCategories()
    {
        $OSCOM_Language = Registry::get('Language');

        if (!isset(static::$_categories)) {
            $data = [
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            static::$_categories = OSCOM::callDB('Website\GetPartnerCategories', $data, 'Site');
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

    public static function getPromotions()
    {
        $OSCOM_Language = Registry::get('Language');

        if (!isset(static::$_promotions)) {
            $data = [
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            static::$_promotions = OSCOM::callDB('Website\GetPartnerPromotions', $data, 'Site');
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

    public static function save(int $user_id, string $code, array $partner): bool
    {
        $campaign = static::getCampaign($user_id, $code);

        $fields = [
            'desc_short' => $partner['desc_short'] ?? null,
            'desc_long' => $partner['desc_long'] ?? null,
            'address' => $partner['address'] ?? null,
            'telephone' => $partner['telephone'] ?? null,
            'email' => $partner['email'] ?? null,
            'youtube_video_id' => $partner['youtube_video_id'] ?? null,
            'url' => $partner['url'] ?? null,
            'public_url' => $partner['public_url'] ?? null,
            'image_small' => $partner['image_small'] ?? null,
            'image_big' => $partner['image_big'] ?? null,
            'image_promo' => $partner['image_promo'] ?? null,
            'image_promo_url' => $partner['image_promo_url'] ?? null,
            'banner_image_en' => $partner['banner_image_en'] ?? null,
            'banner_url_en' => $partner['banner_url_en'] ?? null,
            'status_update_en' => $partner['status_update_en'] ?? null,
            'banner_image_de' => $partner['banner_image_de'] ?? null,
            'banner_url_de' => $partner['banner_url_de'] ?? null,
            'status_update_de' => $partner['status_update_de'] ?? null,
            'carousel_image' => $partner['carousel_image'] ?? null,
            'carousel_title' => $partner['carousel_title'] ?? null,
            'carousel_url' => $partner['carousel_url'] ?? null,
            'billing_address' => $partner['billing_address'] ?? null,
            'billing_vat_id' => $partner['billing_vat_id'] ?? null
        ];

        $data = [
            'id' => $campaign['id']
        ];

        foreach ($fields as $k => $v) {
            if ($v !== null) {
                $data[$k] = $v;
            }
        }

        if ((count($data) > 1) && (OSCOM::callDB('Website\PartnerSave', $data, 'Site') > 0)) {
            static::auditLog($campaign, $data);

            Cache::clear('website_partner-' . $code);
            Cache::clear('website_partner_promotions');
            Cache::clear('website_partners');
            Cache::clear('website_carousel_frontpage');

            return true;
        }

        return false;
    }

    protected static function auditLog(array $orig, array $new)
    {
        $diff = array_diff_assoc($new, $orig);

// new file uploads may share the same name as existing files so they are added manually to the array diff
        if (isset($new['image_small']) && ($new['image_small'] == $orig['image_small'])) {
            $diff['image_small'] = $new['image_small'];
        }

        if (isset($new['image_big']) && ($new['image_big'] == $orig['image_big'])) {
            $diff['image_big'] = $new['image_big'];
        }

        if (isset($new['image_promo']) && ($new['image_promo'] == $orig['image_promo'])) {
            $diff['image_promo'] = $new['image_promo'];
        }

        if (isset($new['banner_image_en']) && ($new['banner_image_en'] == $orig['banner_image_en'])) {
            $diff['banner_image_en'] = $new['banner_image_en'];
        }

        if (isset($new['banner_image_de']) && ($new['banner_image_de'] == $orig['banner_image_de'])) {
            $diff['banner_image_de'] = $new['banner_image_de'];
        }

        if (isset($new['carousel_image']) && ($new['carousel_image'] == $orig['carousel_image'])) {
            $diff['carousel_image'] = $new['carousel_image'];
        }

        if (!empty($diff)) {
            $data = [
                'action' => 'Partner',
                'id' => $orig['id'],
                'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'],
                'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
                'action_type' => 'update',
                'rows' => []
            ];

            foreach ($diff as $key => $new_value) {
                $data['rows'][] = [
                    'key' => $key,
                    'old' => $orig[$key] ?? null,
                    'new' => $new_value
                ];
            }

            AuditLog::save($data);
        }
    }

    public static function getProductPlan($plan, $duration)
    {
        $result = [
            'plan' => ($plan == 'silver' ? 'Silver' : 'Gold') . ' Level',
            'duration' => $duration . ' ' . ($duration > 1 ? ' Months' : 'Month')
        ];

        if ($plan == 'silver') {
            $prices = [
                '1' => '50',
                '3' => '140',
                '6' => '250',
                '12' => '500'
            ];
        } else {
            $prices = [
                '1' => '100',
                '3' => '280',
                '6' => '500',
                '12' => '1000',
                '18' => '1500',
                '24' => '2000'
            ];
        }

        $result['price'] = $prices[$duration];

        return $result;
    }

    public static function getPackages()
    {
        $OSCOM_Language = Registry::get('Language');

        if (isset(static::$packages)) {
            return static::$packages;
        }

        $data = [
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $packages = OSCOM::callDB('Website\GetPartnerPackages', $data, 'Site');

        $result = [];

        foreach ($packages as $pkg) {
            $levels = static::getPackageLevels($pkg['code']);

            $selected_id = null;

            foreach ($levels as $lkey => $lvalue) {
                if ($lvalue['default_selected'] == '1') {
                    $selected_id = $lkey;
                }

                unset($levels[$lkey]['default_selected']);
            }

            $result[$pkg['code']] = [
                'title' => $pkg['title'],
                'title_short' => $pkg['title_short'],
                'selected' => $selected_id,
                'levels' => $levels
            ];
        }

        static::$packages = $result;

        return static::$packages;
    }

    public static function getPackageLevels($code)
    {
        $OSCOM_Language = Registry::get('Language');

        $data = [
            'package_code' => $code,
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $levels = OSCOM::callDB('Website\GetPartnerPackageLevels', $data, 'Site');

        $result = [];

        foreach ($levels as $l) {
            $result[$l['id']] = [
                'title' => $l['title'],
                'duration' => $l['duration_months'],
                'price' => number_format($l['price'], 0),
                'price_raw' => number_format($l['price'], 0, '', ''),
                'default_selected' => $l['default_selected']
            ];
        }

        return $result;
    }

    public static function getPackageId($code) {
        $result = OSCOM::callDB('Website\GetPartnerPackageId', ['code' => $code], 'Site');

        return $result['id'];
    }
  }
?>
