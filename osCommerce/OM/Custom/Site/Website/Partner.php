<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    AuditLog,
    Cache,
    DateTime,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Users;

class Partner
{
    protected static $_partner;
    protected static $_partners;
    protected static $_categories;
    protected static $_promotions;

    public static function get(string $code, string $key = null)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset(static::$_partner[$code])) {
            $data = [
                'code' => $code,
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            $partner = $OSCOM_PDO->call('Get', $data);

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

    public static function getAll(): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $partners = $OSCOM_PDO->call('GetAll', $data);

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

    public static function getInCategory(string $code): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset(static::$_partners[$code])) {
            $data = [
                'code' => $code,
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            $partners = $OSCOM_PDO->call('GetInCategory', $data);

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

    public static function exists(string $code, string $category = null): bool
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

    public static function getCategory(string $code, string $key = null)
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

    public static function getCategories(): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset(static::$_categories)) {
            $data = [
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            static::$_categories = $OSCOM_PDO->call('GetCategories', $data);
        }

        return static::$_categories;
    }

    public static function categoryExists(string $code): bool
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

    public static function getPromotions(): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset(static::$_promotions)) {
            $data = [
                'default_language_id' => $OSCOM_Language->getDefaultId()
            ];

            if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
                $data['language_id'] = $OSCOM_Language->getID();
            }

            $partners = $OSCOM_PDO->call('GetPromotions', $data);

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

    public static function hasCampaign(int $user_id, string $code = null): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'id' => $user_id
        ];

        if (isset($code)) {
            $data['code'] = $code;
        }

        return $OSCOM_PDO->call('HasCampaign', $data);
    }

    public static function getCampaigns(int $user_id): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'id' => $user_id,
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $campaigns = $OSCOM_PDO->call('GetCampaigns', $data);

        $dateNow = new \DateTime();

        foreach ($campaigns as $key => $c) {
            $dateEnd = new \DateTime($c['date_end']);
            $dateDiff = $dateNow->diff($dateEnd);

            $campaigns[$key]['status'] = ($dateDiff->format('%R') === '+') ? 1 : 0;
            $campaigns[$key]['relative_date'] = DateTime::getRelative($dateEnd);
        }

        return $campaigns;
    }

    public static function getCampaign(int $user_id, string $code): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'id' => $user_id,
            'code' => $code
        ];

        $campaign = $OSCOM_PDO->call('GetCampaign', $data);

        $dateNow = new \DateTime();

        $dateEnd = new \DateTime($campaign['date_end']);
        $dateDiff = $dateNow->diff($dateEnd);

        $campaign['status'] = ($dateDiff->format('%R') === '+') ? 1 : 0;
        $campaign['relative_date'] = DateTime::getRelative($dateEnd);

        return $campaign;
    }

    public static function getCampaignInfo(int $partner_id, int $language_id = null): ?array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset($language_id)) {
            $language_id = $OSCOM_Language->getID();
        }

        return $OSCOM_PDO->call('GetCampaignInfo', ['id' => $partner_id, 'language_id' => $language_id]);
    }

    public static function getCampaignAdmins(string $code): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->call('GetCampaignAdmins', ['code' => $code]);
    }

    public static function getStatusUpdateUrl(string $code, string $url_id)
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->call('GetStatusUpdateUrl', ['partner_id' => static::get($code, 'id'), 'id' => $url_id]);
    }

    public static function getAudit(string $code): array
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
        if (is_null($campaign)) {
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

                        if (is_null($campaign)) {
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

        if ((count($data) > 2) && ($OSCOM_PDO->call('Save', $data) > 0)) {
            static::auditLog($campaign, $data);

            Cache::clear('website_partner-' . $code);
            Cache::clear('website_partner_categories');
            Cache::clear('website_partner_promotions');
            Cache::clear('website_partners');
            Cache::clear('carousel-website-frontpage');

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

    public static function getPackages(string $partner_code): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $packages = $OSCOM_PDO->call('GetPackages', $data);

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

    public static function getPackageLevels(string $code, string $partner_code): array
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');

        $partner = static::get($partner_code);

        $result = [];

        $data = [
            'package_code' => $code,
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $has_exclusive_offer = false;

        if (isset($partner_code)) {
            $extra = array_merge($data, ['partner_id' => static::get($partner_code, 'id')]);

            $levels = $OSCOM_PDO->call('GetPackageLevelsExtra', $extra);

            if (!empty($levels)) {
                foreach ($levels as $l) {
                    $result[$l['id']] = [
                        'title' => $l['title'],
                        'duration' => $l['duration_months'],
                        'price' => '€' . $OSCOM_Language->formatNumber($l['price'], 2),
                        'price_raw' => number_format($l['price'], 2, '.', ''),
                        'default_selected' => $l['default_selected']
                    ];

                    $result[$l['id']]['total'] = $result[$l['id']]['price'];
                    $result[$l['id']]['total_raw'] = $result[$l['id']]['price_raw'];

                    if ($partner['billing_country_iso_code_2'] == 'DE') {
                        $result[$l['id']]['tax']['DE19MWST'] = '€' . $OSCOM_Language->formatNumber(0.19 * $l['price'], 2);
                        $result[$l['id']]['tax_raw']['DE19MWST'] = number_format(0.19 * $l['price'], 2, '.', '');

                        $result[$l['id']]['total'] = '€' . $OSCOM_Language->formatNumber(1.19 * $l['price'], 2);
                        $result[$l['id']]['total_raw'] = number_format(1.19 * $l['price'], 2, '.', '');
                    }

                    if ((int)$l['exclusive_offer'] === 1) {
                        $has_exclusive_offer = true;
                    }
                }
            }
        }

        if ($has_exclusive_offer === false) {
            $levels = $OSCOM_PDO->call('GetPackageLevels', $data);

            foreach ($levels as $l) {
                $result[$l['id']] = [
                    'title' => $l['title'],
                    'duration' => $l['duration_months'],
                    'price' => '€' . $OSCOM_Language->formatNumber($l['price'], 2),
                    'price_raw' => number_format($l['price'], 2, '.', ''),
                    'default_selected' => $l['default_selected']
                ];

                $result[$l['id']]['total'] = $result[$l['id']]['price'];
                $result[$l['id']]['total_raw'] = $result[$l['id']]['price_raw'];

                if ($partner['billing_country_iso_code_2'] == 'DE') {
                    $result[$l['id']]['tax']['DE19MWST'] = '€' . $OSCOM_Language->formatNumber(0.19 * $l['price'], 2);
                    $result[$l['id']]['tax_raw']['DE19MWST'] = number_format(0.19 * $l['price'], 2, '.', '');

                    $result[$l['id']]['total'] = '€' . $OSCOM_Language->formatNumber(1.19 * $l['price'], 2);
                    $result[$l['id']]['total_raw'] = number_format(1.19 * $l['price'], 2, '.', '');
                }
            }
        }

        return $result;
    }

    public static function getPackageId(string $code): int
    {
        $OSCOM_PDO = Registry::get('PDO');

        $result = $OSCOM_PDO->call('GetPackageId', ['code' => $code]);

        return $result['id'];
    }

    public static function updatePackageLevelStatus($id)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $level = $OSCOM_PDO->call('GetPackageLevel', ['id' => $id]);

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
