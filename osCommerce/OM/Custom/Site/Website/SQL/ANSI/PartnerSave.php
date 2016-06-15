<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class PartnerSave
{
    const LANGUAGES = [
        'en',
        'de'
    ];

    public static function execute(array $data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $affected_rows = 0;

        $fields = [
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
            'billing_address' => $data['billing_address'] ?? null,
            'billing_vat_id' => $data['billing_vat_id'] ?? null
        ];

        $partner = [];

        foreach ($fields as $k => $v) {
            if ($v !== null) {
                $partner[$k] = $v;
            }
        }

        try {
            $OSCOM_PDO->beginTransaction();

            if (!empty($partner)) {
                if ($OSCOM_PDO->save('website_partner', $partner, ['id' => $data['id']]) === 1) {
                    $affected_rows += 1;
                }
            }

            foreach (static::LANGUAGES as $lang) {
                if (isset($data['banner_url_' . $lang]) || isset($data['banner_image_' . $lang])) {
                    $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_banner where partner_id = :partner_id and code = :code');
                    $Qcheck->bindInt(':partner_id', $data['id']);
                    $Qcheck->bindValue(':code', $lang);
                    $Qcheck->execute();

                    if ($Qcheck->fetch() !== false) {
                        if (isset($data['banner_url_' . $lang]) && empty($data['banner_url_' . $lang]) && isset($data['banner_image_' . $lang]) && empty($data['banner_image_' . $lang])) {
                            if ($OSCOM_PDO->delete('website_partner_banner', ['id' => $Qcheck->valueInt('id')]) === 1) {
                                $affected_rows += 1;
                            }
                        } else {
                            $banner = [];

                            if (isset($data['banner_url_' . $lang])) {
                                $banner['url'] = $data['banner_url_' . $lang];
                            }

                            if (isset($data['banner_image_' . $lang])) {
                                $banner['image'] = $data['banner_image_' . $lang];
                            }

                            if ($OSCOM_PDO->save('website_partner_banner', $banner, ['id' => $Qcheck->valueInt('id')]) === 1) {
                                $affected_rows += 1;
                            }
                        }
                    } elseif ((isset($data['banner_url_' . $lang]) && !empty($data['banner_url_' . $lang])) || (isset($data['banner_image_' . $lang]) && !empty($data['banner_image_' . $lang]))) {
                        $banner = [
                            'partner_id' => $data['id'],
                            'code' => $lang
                        ];

                        if (isset($data['banner_url_' . $lang])) {
                            $banner['url'] = $data['banner_url_' . $lang];
                        }

                        if (isset($data['banner_image_' . $lang])) {
                            $banner['image'] = $data['banner_image_' . $lang];
                        }

                        if ($OSCOM_PDO->save('website_partner_banner', $banner) === 1) {
                            $affected_rows += 1;
                        }
                    }
                }

                if (isset($data['status_update_' . $lang])) {
                    $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update where partner_id = :partner_id and code = :code');
                    $Qcheck->bindInt(':partner_id', $data['id']);
                    $Qcheck->bindValue(':code', $lang);
                    $Qcheck->execute();

                    if ($Qcheck->fetch() !== false) {
                        if (empty($data['status_update_' . $lang])) {
                            if ($OSCOM_PDO->delete('website_partner_status_update', ['id' => $Qcheck->valueInt('id')]) === 1) {
                                $affected_rows += 1;
                            }
                        } else {
                            $status = [
                                'status_update' => $data['status_update_' . $lang]
                            ];

                            if ($OSCOM_PDO->save('website_partner_status_update', $status, ['id' => $Qcheck->valueInt('id')]) === 1) {
                                $affected_rows += 1;
                            }
                        }
                    } elseif (!empty($data['status_update_' . $lang])) {
                        $status = [
                            'partner_id' => $data['id'],
                            'code' => $lang,
                            'status_update' => $data['status_update_' . $lang]
                        ];

                        if ($OSCOM_PDO->save('website_partner_status_update', $status) === 1) {
                            $affected_rows += 1;
                        }
                    }
                }
            }

            if ($OSCOM_PDO->commit()) {
                return $affected_rows;
            }
        } catch (\Exception $e) {
            $OSCOM_PDO->rollBack();

            trigger_error($e->getMessage());
        }

        return false;
    }
}
