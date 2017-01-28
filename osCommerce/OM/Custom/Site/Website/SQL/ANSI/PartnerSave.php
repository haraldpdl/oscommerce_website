<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class PartnerSave
{
    public static function execute(array $data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $result = false;
        $affected_rows = 0;

        $partner_raw = [
            'billing_address' => $data['billing_address'] ?? null,
            'billing_vat_id' => $data['billing_vat_id'] ?? null
        ];

        $partner = [];

        foreach ($partner_raw as $k => $v) {
            if ($v !== null) {
                $partner[$k] = $v;
            }
        }

        $partner_info_raw = [
            'desc_short' => $data['desc_short'] ?? null,
            'desc_long' => $data['desc_long'] ?? null,
            'address' => $data['address'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'url' => $data['url'] ?? null,
            'public_url' => $data['public_url'] ?? null,
            'image_small' => $data['image_small'] ?? null,
            'image_big' => $data['image_big'] ?? null,
            'image_promo' => $data['image_promo'] ?? null,
            'image_promo_url' => $data['image_promo_url'] ?? null,
            'youtube_video_id' => $data['youtube_video_id'] ?? null,
            'carousel_image' => $data['carousel_image'] ?? null,
            'carousel_title' => $data['carousel_title'] ?? null,
            'carousel_url' => $data['carousel_url'] ?? null,
            'banner_image' => $data['banner_image'] ?? null,
            'banner_url' => $data['banner_url'] ?? null,
            'status_update' => $data['status_update'] ?? null
        ];

        $partner_info = [];

        foreach ($partner_info_raw as $k => $v) {
            if ($v !== null) {
                $partner_info[$k] = $v;
            }
        }

        try {
            if (!empty($partner) || !empty($partner_info)) {
                $OSCOM_PDO->beginTransaction();

                if (!empty($partner)) {
                    if ($OSCOM_PDO->save('website_partner', $partner, ['id' => $data['id']]) === 1) {
                        $affected_rows += 1;
                    }
                }

                if (!empty($partner_info)) {
                    if ($OSCOM_PDO->save('website_partner_info', $partner_info, ['partner_id' => $data['id'], 'languages_id' => $data['language_id']]) === 1) {
                        $affected_rows += 1;
                    }
                }

                $OSCOM_PDO->commit();
            }

            $result = $affected_rows;
        } catch (\Exception $e) {
            $OSCOM_PDO->rollBack();

            trigger_error($e->getMessage());
        }

        return $result;
    }
}
