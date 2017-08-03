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

class Partner
{
    protected static $_partner;
    protected static $_partners;
    protected static $_categories;
    protected static $_promotions;

    public static function get($code, $key = null)
    {
        $OSCOM_Language = Registry::get('Language');

        if (!isset(static::$_partner[$code])) {
            $data = [
                'code' => $code,
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            $partner = OSCOM::callDB('Website\GetPartner', $data, 'Site');

            $languages = [
                $OSCOM_Language->getCode()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $languages[] = $OSCOM_Language->getCodeFromID($OSCOM_Language->getDefaultId());
            }

            $partner['image_small_path'] = null;

            if (!empty($partner['image_small'])) {
                foreach ($languages as $l) {
                    if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $l . '/' . $partner['image_small'])) {
                        $partner['image_small_path'] = 'images/partners/' . $l . '/' . $partner['image_small'];

                        break;
                    }
                }
            }

            $partner['image_big_path'] = null;

            if (!empty($partner['image_big'])) {
                foreach ($languages as $l) {
                    if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $l . '/' . $partner['image_big'])) {
                        $partner['image_big_path'] = 'images/partners/' . $l . '/' . $partner['image_big'];

                        break;
                    }
                }
            }

            static::$_partner[$code] = $partner;
        }

        return isset($key) ? static::$_partner[$code][$key] : static::$_partner[$code];
    }

    public static function getAll()
    {
        $OSCOM_Language = Registry::get('Language');

        $data = [
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $partners = OSCOM::callDB('Website\GetPartnersAll', $data, 'Site');

        $languages = [
            $OSCOM_Language->getCode()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $languages[] = $OSCOM_Language->getCodeFromID($OSCOM_Language->getDefaultId());
        }

        foreach ($partners as $k => $p) {
            $partners[$k]['image_small_path'] = null;

            if (!empty($p['image_small'])) {
                foreach ($languages as $l) {
                    if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $l . '/' . $p['image_small'])) {
                        $partners[$k]['image_small_path'] = 'images/partners/' . $l . '/' . $p['image_small'];

                        break;
                    }
                }
            }
        }

        return $partners;
    }

    public static function getInCategory($code)
    {
        $OSCOM_Language = Registry::get('Language');

        if (!isset(static::$_partners[$code])) {
            $data = [
                'code' => $code,
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            $partners = OSCOM::callDB('Website\GetPartners', $data, 'Site');

            $languages = [
                $OSCOM_Language->getCode()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $languages[] = $OSCOM_Language->getCodeFromID($OSCOM_Language->getDefaultId());
            }

            foreach ($partners as $k => $p) {
                $partners[$k]['image_small_path'] = null;

                if (!empty($p['image_small'])) {
                    foreach ($languages as $l) {
                        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $l . '/' . $p['image_small'])) {
                            $partners[$k]['image_small_path'] = 'images/partners/' . $l . '/' . $p['image_small'];

                            break;
                        }
                    }
                }
            }

            static::$_partners[$code] = $partners;
        }

        return static::$_partners[$code];
    }

    public static function exists($code, $category = null)
    {
        if (isset($category)) {
            if (!isset(static::$_partners[$category])) {
                static::getInCategory($category);
            }

            foreach (static::$_partners[$category] as $p) {
                if ($p['code'] == $code) {
                    return true;
                }
            }
        } else {
            $partner = static::get($code);

            return is_array($partner) && !empty($partner);
        }

        return false;
    }

    public static function getCategory($code, $key = null)
    {
        if (!isset(static::$_categories)) {
            static::getCategories();
        }

        foreach (static::$_categories as $c) {
            if ($c['code'] == $code) {
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

    public static function categoryExists($code)
    {
        if (!isset(static::$_categories)) {
            static::getCategories();
        }

        foreach (static::$_categories as $c) {
            if ($c['code'] == $code) {
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

            $partners = OSCOM::callDB('Website\GetPartnerPromotions', $data, 'Site');

            $languages = [
                $OSCOM_Language->getCode()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $languages[] = $OSCOM_Language->getCodeFromID($OSCOM_Language->getDefaultId());
            }

            foreach ($partners as $k => $p) {
                $partners[$k]['image_promo_path'] = null;

                if (!empty($p['image_promo'])) {
                    foreach ($languages as $l) {
                        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $l . '/' . $p['image_promo'])) {
                            $partners[$k]['image_promo_path'] = 'images/partners/' . $l . '/' . $p['image_promo'];

                            break;
                        }
                    }
                }
            }

            static::$_promotions = $partners;
        }

        return static::$_promotions;
    }

    public static function hasCampaign($id, $code = null)
    {
        $data = [
            'id' => $id
        ];

        if (isset($code)) {
            $data['code'] = $code;
        }

        return OSCOM::callDB('Website\PartnerHasCampaign', $data, 'Site');
    }

    public static function getCampaigns($id)
    {
        $OSCOM_Language = Registry::get('Language');

        $data = [
            'id' => $id,
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        return OSCOM::callDB('Website\PartnerGetCampaigns', $data, 'Site');
    }

    public static function getCampaign($id, $code)
    {
        $data = [
            'id' => $id,
            'code' => $code
        ];

        return OSCOM::callDB('Website\PartnerGetCampaign', $data, 'Site');
    }

    public static function getCampaignInfo($id, $language_id = null)
    {
        $OSCOM_Language = Registry::get('Language');

        if (!isset($language_id)) {
            $language_id = $OSCOM_Language->getID();
        }

        return OSCOM::callDB('Website\PartnerGetCampaignInfo', ['id' => $id, 'language_id' => $language_id], 'Site');
    }

    public static function getCampaignAdmins($code)
    {
        return OSCOM::callDB('Website\PartnerGetCampaignAdmins', ['code' => $code], 'Site');
    }

    public static function getStatusUpdateUrl($code, $url_id)
    {
        return OSCOM::callDB('Website\GetPartnerStatusUpdateUrl', ['partner_id' => static::get($code, 'id'), 'id' => $url_id], 'Site');
    }

    public static function getAudit($code)
    {
        $OSCOM_Cache = new Cache();

        $id = $code;

        if (!is_numeric($id)) {
            $id = static::get($id, 'id');
        }

        if ($OSCOM_Cache->read('website_partner-' . $code . '-audit')) {
            $result = $OSCOM_Cache->getCache();
        } else {
            $result = AuditLog::getAll('Website\Account\Partner', $id, 6);

            foreach ($result as &$record) {
                $record['user_name'] = Users::get((int)$record['user_id'], 'name');
                $record['date_added'] = (new \DateTime($record['date_added']))->format('jS M Y H:i');
            }

            $OSCOM_Cache->write($result);
        }

        return $result;
    }

    public static function save(int $user_id, string $code, array $partner, int $language_id = null): bool
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset($language_id)) {
            $language_id = $OSCOM_Language->getId();
        }

        $partner_id = static::get($code, 'id');

        $campaign = static::getCampaignInfo($partner_id, $language_id);

// automatically create campaign in new language
        if ($campaign === false) {
            if (($language_id !== $OSCOM_Language->getDefaultId()) && $OSCOM_Language->exists($OSCOM_Language->getCodeFromID($language_id))) {
                $orig = static::getCampaignInfo($partner_id, $OSCOM_Language->getDefaultId());

                if ($orig !== false) {
                    $o = [
                        'partner_id' => $partner_id,
                        'languages_id' => $language_id,
                        'title' => $orig['title'],
                        'code' => $orig['code']
                    ];

                    if ($OSCOM_PDO->save('website_partner_info', $o) === 1) {
                        $campaign = static::getCampaignInfo($partner_id, $language_id);

                        if ($campaign === false) {
                            return false;
                        }
                    }
                }
            }
        }

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
            'banner_image' => $partner['banner_image'] ?? null,
            'banner_url' => $partner['banner_url'] ?? null,
            'status_update' => $partner['status_update'] ?? null,
            'carousel_image' => $partner['carousel_image'] ?? null,
            'carousel_title' => $partner['carousel_title'] ?? null,
            'carousel_url' => $partner['carousel_url'] ?? null,
            'billing_address' => $partner['billing_address'] ?? null,
            'billing_vat_id' => $partner['billing_vat_id'] ?? null
        ];

        $data = [
            'id' => $partner_id,
            'language_id' => $language_id
        ];

        foreach ($fields as $k => $v) {
            if ($v !== null) {
                $data[$k] = $v;
            }
        }

        if ((count($data) > 2) && (OSCOM::callDB('Website\PartnerSave', $data, 'Site') > 0)) {
            static::auditLog($campaign, $data);

            Cache::clear('website_partner-' . $code);
            Cache::clear('website_partner_categories');
            Cache::clear('website_partner_promotions');
            Cache::clear('website_partners');
            Cache::clear('website_carousel_frontpage');

            return true;
        }

        return false;
    }

    protected static function auditLog(array $orig, array $new)
    {
        $OSCOM_Language = Registry::get('Language');

        $partner_id = $new['id'];
        $language_id = $new['language_id'];

        unset($new['id']);
        unset($new['language_id']);

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

        if (isset($new['banner_image']) && ($new['banner_image'] == $orig['banner_image'])) {
            $diff['banner_image'] = $new['banner_image'];
        }

        if (isset($new['carousel_image']) && ($new['carousel_image'] == $orig['carousel_image'])) {
            $diff['carousel_image'] = $new['carousel_image'];
        }

        if (!empty($diff)) {
            $data = [
                'action' => 'Partner',
                'id' => $partner_id,
                'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'],
                'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
                'action_type' => 'update',
                'rows' => []
            ];

            foreach ($diff as $key => $new_value) {
                $data['rows'][] = [
                    'key' => $key . ' [' . $OSCOM_Language->getCodeFromID($language_id) . ']',
                    'old' => $orig[$key] ?? null,
                    'new' => $new_value
                ];
            }

            AuditLog::save($data);
        }
    }

    public static function getPackages($partner_code = null)
    {
        $OSCOM_Language = Registry::get('Language');

        $data = [
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $packages = OSCOM::callDB('Website\GetPartnerPackages', $data, 'Site');

        $result = [];

        foreach ($packages as $pkg) {
            $levels = static::getPackageLevels($pkg['code'], $partner_code);

            $selected_id = null;

            foreach ($levels as $lkey => $lvalue) {
                if (!isset($selected_id) && ($lvalue['default_selected'] == '1')) {
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

        return $result;
    }

    public static function getPackageLevels($code, $partner_code = null)
    {
        $OSCOM_Language = Registry::get('Language');

        $partner = static::get($partner_code);

        $result = [];

        $data = [
            'package_code' => $code,
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        if (isset($partner_code)) {
            $extra = array_merge($data, ['partner_id' => static::get($partner_code, 'id')]);

            $levels = OSCOM::callDB('Website\GetPartnerPackageLevelsExtra', $extra, 'Site');

            if (!empty($levels)) {
                foreach ($levels as $l) {
                    $result[$l['id']] = [
                        'title' => $l['title'],
                        'duration' => $l['duration_months'],
                        'price' => number_format((($partner['billing_country_iso_code_2'] == 'DE') && empty($partner['billing_vat_id'])) ? 1.19 * $l['price'] : $l['price'], 0),
                        'price_raw' => number_format($l['price'], 0, '', ''),
                        'tax' => (($partner['billing_country_iso_code_2'] == 'DE') && empty($partner['billing_vat_id'])) ? 0.19 * $l['price'] : 0,
                        'default_selected' => $l['default_selected']
                    ];
                }
            }
        }

        $levels = OSCOM::callDB('Website\GetPartnerPackageLevels', $data, 'Site');

        foreach ($levels as $l) {
            $result[$l['id']] = [
                'title' => $l['title'],
                'duration' => $l['duration_months'],
                'price' => number_format((($partner['billing_country_iso_code_2'] == 'DE') && empty($partner['billing_vat_id'])) ? 1.19 * $l['price'] : $l['price'], 0),
                'price_raw' => number_format($l['price'], 0, '', ''),
                'tax' => (($partner['billing_country_iso_code_2'] == 'DE') && empty($partner['billing_vat_id'])) ? 0.19 * $l['price'] : 0,
                'default_selected' => $l['default_selected']
            ];
        }

        return $result;
    }

    public static function getPackageId($code)
    {
        $result = OSCOM::callDB('Website\GetPartnerPackageId', ['code' => $code], 'Site');

        return $result['id'];
    }

    public static function updatePackageLevelStatus($id)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $level = OSCOM::callDB('Website\GetPartnerPackageLevel', ['id' => $id], 'Site');

        if ((int)$level['usage_counter'] > 0) {
            $counter = (int)$level['usage_counter'] - 1;

            $data = [
                'usage_counter' => $counter,
                'status' => ($counter > 0) ? 1 : 0
            ];

            $OSCOM_PDO->save('website_partner_package_levels', $data, ['id' => $id]);
        }
    }
}
