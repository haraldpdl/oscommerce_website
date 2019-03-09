<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Edit;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    Hash,
    HttpRequest,
    Mail,
    OSCOM,
    Registry,
    Sanitize,
    Upload
};

use osCommerce\OM\Core\Site\Website\{
    Partner,
    Users
};

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $data = [];
        $error = false;

        $public_token = Sanitize::simple($_POST['public_token'] ?? null);

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        $partner = $OSCOM_Template->getValue('partner');
        $partner_campaign = $OSCOM_Template->getValue('partner_campaign');

        $campaigns = [];

        foreach ($OSCOM_Language->getAll() as $l) {
            $campaigns[$l['code']] = $OSCOM_Template->getValue('partner_campaign_' . $l['code']);
        }

        foreach ($OSCOM_Language->getAll() as $l) {
            $input = Sanitize::simple($_POST['desc_short_' . $l['code']] ?? null);
            $data[$l['code']]['desc_short'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['desc_short']) > 450) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_desc_short_length'));
            }

            $input = Sanitize::para($_POST['desc_long_' . $l['code']] ?? null);
            $data[$l['code']]['desc_long'] = !empty($input) ? $input : null;

            $input = Sanitize::para($_POST['address_' . $l['code']] ?? null);
            $data[$l['code']]['address'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['address']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_address_length'));
            }

            $input = Sanitize::simple($_POST['telephone_' . $l['code']] ?? null);
            $data[$l['code']]['telephone'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['telephone']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_telephone_length'));
            }

            $input = Sanitize::simple($_POST['email_' . $l['code']] ?? null);
            $data[$l['code']]['email'] = !empty($input) ? $input : null;

            if (!empty($data[$l['code']]['email']) && ((filter_var($data[$l['code']]['email'], FILTER_VALIDATE_EMAIL) === false) || (strlen($data[$l['code']]['email']) > 255))) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_email_length'));
            }

            $input = Sanitize::simple($_POST['youtube_video_id_' . $l['code']] ?? null);
            $data[$l['code']]['youtube_video_id'] = !empty($input) ? $input : '';

            if (strlen($data[$l['code']]['youtube_video_id']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_youtube_video_id_length'));
            } else {
                if (!empty($data[$l['code']]['youtube_video_id'])) {
                    $result = HttpRequest::getResponse([
                        'url' => 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $data[$l['code']]['youtube_video_id'] . '&key=' . OSCOM::getConfig('youtube_api_key', 'Website'),
                        'method' => 'get'
                    ]);

                    if (!empty($result)) {
                        $result = json_decode($result, true);
                    }

                    if (!is_array($result) || !isset($result['pageInfo']) || ($result['pageInfo']['totalResults'] !== 1)) {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_youtube_video_id_invalid'));
                    }
                }
            }

            $input = Sanitize::simple($_POST['public_url_' . $l['code']] ?? null);
            $data[$l['code']]['public_url'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['public_url']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_public_url_length'));
            }

            $input = Sanitize::simple($_POST['url_' . $l['code']] ?? null);
            $data[$l['code']]['url'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['url']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_url_length'));
            } elseif (!empty($data[$l['code']]['url']) && preg_match('/^(http|https)\:\/\/.+/', $data[$l['code']]['url']) !== 1) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_url_invalid'));
            }

            $data[$l['code']]['image_small'] = null;

            if (isset($_FILES['image_small_' . $l['code']]['name']) && !empty($_FILES['image_small_' . $l['code']]['name'])) {
                $Uimage = new Upload('image_small_' . $l['code'], OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners/' . $l['code'], null, ['gif', 'jpg', 'png'], true);

                if ($Uimage->check()) {
                    $image = getimagesize($_FILES['image_small_' . $l['code']]['tmp_name']);

                    if (($image !== false) && ($image[0] == '130') && ($image[1] == '50')) {
                        $Uimage->setFilename($campaigns[$l['code']]['code'] . '-' . Hash::getRandomString(4) . '.' . $Uimage->getExtension());

                        $data[$l['code']]['image_small'] = clone $Uimage;
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_small_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_small_error'));
                }
            }

            $data[$l['code']]['image_big'] = null;

            if (isset($_FILES['image_big_' . $l['code']]['name']) && !empty($_FILES['image_big_' . $l['code']]['name'])) {
                $Uimage = new Upload('image_big_' . $l['code'], OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners/' . $l['code'], null, ['jpg', 'png'], true);

                if ($Uimage->check()) {
                    $image = getimagesize($_FILES['image_big_' . $l['code']]['tmp_name']);

                    if (($image !== false) && ($image[0] == '1920') && ($image[1] == '1080') && ($_FILES['image_big_' . $l['code']]['size'] <= 358400)) {
                        $Uimage->setFilename($campaigns[$l['code']]['code'] . '_header-' . Hash::getRandomString(4) . '.' . $Uimage->getExtension());

                        $data[$l['code']]['image_big'] = clone $Uimage;
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_big_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_big_error'));
                }
            }

            $data[$l['code']]['image_promo'] = null;

            if (isset($_FILES['image_promo_' . $l['code']]['name']) && !empty($_FILES['image_promo_' . $l['code']]['name'])) {
                $Uimage = new Upload('image_promo_' . $l['code'], OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners/' . $l['code'], null, ['gif', 'jpg', 'png'], true);

                if ($Uimage->check()) {
                    $image = getimagesize($_FILES['image_promo_' . $l['code']]['tmp_name']);

                    if (($image !== false) && ($image[0] == '150') && ($image[1] == '100')) {
                        $Uimage->setFilename($campaigns[$l['code']]['code'] . '_promo-' . Hash::getRandomString(4) . '.' . $Uimage->getExtension());

                        $data[$l['code']]['image_promo'] = clone $Uimage;
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_error'));
                }
            }

            $input = Sanitize::simple($_POST['image_promo_url_' . $l['code']] ?? null);
            $data[$l['code']]['image_promo_url'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['image_promo_url']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_url_length'));
            } elseif (!empty($data[$l['code']]['image_promo_url']) && preg_match('/^(http|https)\:\/\/.+/', $data[$l['code']]['image_promo_url']) !== 1) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_url_invalid'));
            }

            $data[$l['code']]['banner_image'] = null;

            if (isset($_FILES['banner_image_' . $l['code']]['name']) && !empty($_FILES['banner_image_' . $l['code']]['name'])) {
                $Uimage = new Upload('banner_image_' . $l['code'], OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners/' . $l['code'], null, ['gif', 'jpg', 'png'], true);

                if ($Uimage->check()) {
                    $image = getimagesize($_FILES['banner_image_' . $l['code']]['tmp_name']);

                    if (($image !== false) && ($image[0] == '468') && ($image[1] == '60')) {
                        $Uimage->setFilename($campaigns[$l['code']]['code'] . '_banner-' . Hash::getRandomString(4) . '.' . $Uimage->getExtension());

                        $data[$l['code']]['banner_image'] = clone $Uimage;
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_image_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_image_error'));
                }
            }

            $input = Sanitize::simple($_POST['banner_url_' . $l['code']] ?? null);
            $data[$l['code']]['banner_url'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['banner_url']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner',  OSCOM::getDef('partner_error_banner_url_length'));
            } elseif (!empty($data[$l['code']]['banner_url']) && preg_match('/^(http|https)\:\/\/.+/', $data[$l['code']]['banner_url']) !== 1) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_url_invalid'));
            }

            $input = Sanitize::simple($_POST['status_update_' . $l['code']] ?? null);
            $data[$l['code']]['status_update'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['status_update']) > 200) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_status_update_length'));
            }

            $data[$l['code']]['carousel_image'] = null;

            if (isset($_FILES['carousel_image_' . $l['code']]['name']) && !empty($_FILES['carousel_image_' . $l['code']]['name'])) {
                $Uimage = new Upload('carousel_image_' . $l['code'], OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners/' . $l['code'], null, ['jpg', 'png'], true);

                if ($Uimage->check()) {
                    $image = getimagesize($_FILES['carousel_image_' . $l['code']]['tmp_name']);

                    if (($image !== false) && ($image[0] == '1920') && ($image[1] == '1080') && ($_FILES['carousel_image_' . $l['code']]['size'] <= 358400)) {
                        $Uimage->setFilename($campaigns[$l['code']]['code'] . '_carousel-' . Hash::getRandomString(4) . '.' . $Uimage->getExtension());

                        $data[$l['code']]['carousel_image'] = clone $Uimage;
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_error'));
                }
            }

            $input = Sanitize::simple($_POST['carousel_image_title_' . $l['code']] ?? null);
            $data[$l['code']]['carousel_image_title'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['carousel_image_title']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_title_length'));
            }

            $input = Sanitize::simple($_POST['carousel_image_url_' . $l['code']] ?? null);
            $data[$l['code']]['carousel_image_url'] = !empty($input) ? $input : null;

            if (strlen($data[$l['code']]['carousel_image_url']) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_url_length'));
            } elseif (!empty($data[$l['code']]['carousel_image_url']) && preg_match('/^(http|https)\:\/\/.+/', $data[$l['code']]['carousel_image_url']) !== 1) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_url_invalid'));
            }
        }

        if ($error === false) {
            $did_save = false;

            foreach ($OSCOM_Language->getAll() as $l) {
                if (isset($data[$l['code']]['image_small'])) {
                    $data[$l['code']]['image_small']->save();
                }

                $pi = [
                    'desc_short' => $data[$l['code']]['desc_short'],
                    'desc_long' => $data[$l['code']]['desc_long'],
                    'address' => $data[$l['code']]['address'],
                    'telephone' => $data[$l['code']]['telephone'],
                    'email' => $data[$l['code']]['email'],
                    'url' => $data[$l['code']]['url'],
                    'public_url' => $data[$l['code']]['public_url'],
                    'image_small' => isset($data[$l['code']]['image_small']) ? $data[$l['code']]['image_small']->getFilename() : null
                ];

                if (isset($data[$l['code']]['image_big'])) {
                    $data[$l['code']]['image_big']->save();
                }

                $pi['image_big'] = isset($data[$l['code']]['image_big']) ? $data[$l['code']]['image_big']->getFilename() : null;

                if (isset($data[$l['code']]['image_promo'])) {
                    $data[$l['code']]['image_promo']->save();
                }

                $pi['image_promo'] = isset($data[$l['code']]['image_promo']) ? $data[$l['code']]['image_promo']->getFilename() : null;
                $pi['image_promo_url'] = $data[$l['code']]['image_promo_url'];

                $pi['youtube_video_id'] = $data[$l['code']]['youtube_video_id'];

                if (isset($data[$l['code']]['carousel_image'])) {
                    $data[$l['code']]['carousel_image']->save();
                }

                $pi['carousel_image'] = isset($data[$l['code']]['carousel_image']) ? $data[$l['code']]['carousel_image']->getFilename() : null;
                $pi['carousel_title'] = $data[$l['code']]['carousel_image_title'];
                $pi['carousel_url'] = $data[$l['code']]['carousel_image_url'];

                if (isset($data[$l['code']]['banner_image'])) {
                    $data[$l['code']]['banner_image']->save();
                }

                $pi['banner_image'] = isset($data[$l['code']]['banner_image']) ? $data[$l['code']]['banner_image']->getFilename() : null;
                $pi['banner_url'] = $data[$l['code']]['banner_url'];

                $pi['status_update'] = $data[$l['code']]['status_update'];

                if (Partner::save($_SESSION[OSCOM::getSite()]['Account']['id'], $partner['code'], $pi, $OSCOM_Language->getID($l['code']))) {
                    $did_save = true;
                }
            }

            if ($did_save === true) {
                $email_txt_file = $OSCOM_Template->getPageContentsFile('email_partner_save.txt');
                $email_txt_tmpl = file_exists($email_txt_file) ? file_get_contents($email_txt_file) : null;

                $email_html_file = $OSCOM_Template->getPageContentsFile('email_partner_save.html');
                $email_html_tmpl = file_exists($email_html_file) ? file_get_contents($email_html_file) : null;

                $OSCOM_Template->setValue('user_name', $_SESSION[OSCOM::getSite()]['Account']['name']);
                $OSCOM_Template->setValue('partner_code', $partner['code']);

                foreach (Partner::getCampaignAdmins($partner['code']) as $admin_id) {
                    $admin = Users::get($admin_id);
                    $OSCOM_Template->setValue('partner_admin_name', $admin['name'], true);

                    $email_txt = null;
                    $email_html = null;

                    if (isset($email_txt_tmpl)) {
                        $email_txt = $OSCOM_Template->parseContent($email_txt_tmpl);
                    }

                    if (isset($email_html_tmpl)) {
                        $email_html = $OSCOM_Template->parseContent($email_html_tmpl);
                    }

                    if (!empty($email_txt) || !empty($email_html)) {
                        $OSCOM_Mail = new Mail($admin['email'], $admin['name'], 'noreply@oscommerce.com', 'osCommerce', OSCOM::getDef('email_partner_update_subject'));

                        if (!empty($email_txt)) {
                            $OSCOM_Mail->setBodyPlain($email_txt);
                        }

                        if (!empty($email_html)) {
                            $OSCOM_Mail->setBodyHTML($email_html);
                        }

                        $OSCOM_Mail->send();
                    }
                }

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_success_save', [
                    ':partner_link' => OSCOM::getLink(null, 'Account', 'Partner&Edit=' . $partner['code'], 'SSL')
                ]), 'success');
            }

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner&View=' . $partner['code'], 'SSL'));
        }
    }
}
