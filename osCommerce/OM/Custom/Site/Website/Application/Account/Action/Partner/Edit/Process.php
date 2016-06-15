<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Edit;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    Mail,
    OSCOM,
    Registry,
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
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $data = [];
        $error = false;

        $public_token = isset($_POST['public_token']) ? trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['public_token'])) : '';

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        $partner = $OSCOM_Template->getValue('partner_campaign');

        if (!isset($_POST['desc_short']) || empty($_POST['desc_short'])) {
            $error = true;

            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_desc_short_empty'));
        } else {
            $desc_short = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['desc_short']));

            if (strlen($desc_short) > 450) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_desc_short_length'));
            } else {
                $data['desc_short'] = $desc_short;
            }
        }

        if (!isset($_POST['desc_long']) || empty($_POST['desc_long'])) {
            $error = true;

            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_desc_long_empty'));
        } else {
            $desc_long = trim($_POST['desc_long']);

            $data['desc_long'] = $desc_long;
        }

        if (isset($_POST['address'])) {
            $address = trim($_POST['address']);

            if (strlen($address) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_address_length'));
            } else {
                $data['address'] = $address;
            }
        }

        if (isset($_POST['telephone'])) {
            $telephone = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['telephone']));

            if (strlen($telephone) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_telephone_length'));
            } else {
                $data['telephone'] = $telephone;
            }
        }

        if (isset($_POST['email'])) {
            $email = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['email']));

            if (!empty($email) && ((filter_var($email, FILTER_VALIDATE_EMAIL) === false) || (strlen($email) > 255))) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_email_length'));
            } else {
                $data['email'] = $email;
            }
        }

        if (isset($_POST['youtube_video_id'])) {
            $youtube_video_id = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['youtube_video_id']));

            if (strlen($youtube_video_id) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_youtube_video_id_length'));
            } else {
                if (!empty($youtube_video_id)) {
                    $curl = curl_init('https://gdata.youtube.com/feeds/api/videos/' . $youtube_video_id);

                    $curl_options = [
                        CURLOPT_HEADER => true,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                        CURLOPT_NOBODY => true,
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_FRESH_CONNECT => true,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_RETURNTRANSFER => true
                    ];

                    curl_setopt_array($curl, $curl_options);
                    $result = curl_exec($curl);

                    if ($result !== false) {
                        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                        if ($http_code !== 200) {
                            $error = true;

                            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_youtube_video_id_invalid'));
                        } else {
                            $data['youtube_video_id'] = $youtube_video_id;
                        }
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_youtube_video_id_invalid'));
                    }
                } else {
                    $data['youtube_video_id'] = $youtube_video_id;
                }
            }
        }

        if (!isset($_POST['public_url']) || empty($_POST['public_url'])) {
            $error = true;

            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_public_url_empty'));
        } else {
            $public_url = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['public_url']));

            if (strlen($public_url) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_public_url_length'));
            } else {
                $data['public_url'] = $public_url;
            }
        }

        if (!isset($_POST['url']) || empty($_POST['url'])) {
            $error = true;

            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_url_empty'));
        } else {
            $url = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['url']));

            if (strlen($url) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_url_length'));
            } elseif (!empty($url) && preg_match('/^(http|https)\:\/\/.+/', $url) !== 1) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_url_invalid'));
            } else {
                $data['url'] = $url;
            }
        }

        if (isset($_FILES['image_small']['name']) && !empty($_FILES['image_small']['name'])) {
            $Uimage_small = new Upload('image_small', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, ['jpg', 'png'], true);

            if ($Uimage_small->check()) {
                $image = getimagesize($_FILES['image_small']['tmp_name']);

                if (($image !== false) && ($image[0] == '130') && ($image[1] == '50')) {
                    $Uimage_small->setFilename($partner['code'] . '.' . $Uimage_small->getExtension());
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_small_error'));
                }
            } else {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_small_error'));
            }
        }

        if ($partner['has_gold'] == '1') {
            if (isset($_FILES['image_big']['name']) && !empty($_FILES['image_big']['name'])) {
                $Uimage_big = new Upload('image_big', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, ['jpg', 'png'], true);

                if ($Uimage_big->check()) {
                    $image = getimagesize($_FILES['image_big']['tmp_name']);

                    if (($image !== false) && ($image[0] == '1200') && ($image[1] == '364')) {
                        $Uimage_big->setFilename($partner['code'] . '_header.' . $Uimage_big->getExtension());
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_big_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_big_error'));
                }
            }

            if (isset($_FILES['image_promo']['name']) && !empty($_FILES['image_promo']['name'])) {
                $Uimage_promo = new Upload('image_promo', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, ['gif', 'jpg', 'png'], true);

                if ($Uimage_promo->check()) {
                    $image = getimagesize($_FILES['image_promo']['tmp_name']);

                    if (($image !== false) && ($image[0] == '150') && ($image[1] == '100')) {
                        $Uimage_promo->setFilename($partner['code'] . '_promo.' . $Uimage_promo->getExtension());
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_error'));
                }
            }

            if (isset($_POST['image_promo_url'])) {
                $image_promo_url = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['image_promo_url']));

                if (strlen($image_promo_url) > 255) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_url_length'));
                } elseif (!empty($image_promo_url) && preg_match('/^(http|https)\:\/\/.+/', $image_promo_url) !== 1) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_image_promo_url_invalid'));
                } else {
                    $data['image_promo_url'] = $image_promo_url;
                }
            }

            if (isset($_FILES['banner_image_en']['name']) && !empty($_FILES['banner_image_en']['name'])) {
                $Ubanner_image_en = new Upload('banner_image_en', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, ['gif', 'jpg', 'png'], true);

                if ($Ubanner_image_en->check()) {
                    $image = getimagesize($_FILES['banner_image_en']['tmp_name']);

                    if (($image !== false) && ($image[0] == '468') && ($image[1] == '60')) {
                        $Ubanner_image_en->setFilename($partner['code'] . '_banner.' . $Ubanner_image_en->getExtension());
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_image_en_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_image_en_error'));
                }
            }

            if (isset($_POST['banner_url_en'])) {
                $banner_url_en = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['banner_url_en']));

                if (strlen($banner_url_en) > 255) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner',  OSCOM::getDef('partner_error_banner_url_en_length'));
                } elseif (!empty($banner_url_en) && preg_match('/^(http|https)\:\/\/.+/', $banner_url_en) !== 1) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_url_en_invalid'));
                } else {
                    $data['banner_url_en'] = $banner_url_en;
                }
            }

            if (isset($_POST['status_update_en'])) {
                $status_update_en = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['status_update_en']));

                if (strlen($status_update_en) > 200) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_status_update_en_length'));
                } else {
                    $data['status_update_en'] = $status_update_en;
                }
            }

            if (isset($_FILES['banner_image_de']['name']) && !empty($_FILES['banner_image_de']['name'])) {
                $Ubanner_image_de = new Upload('banner_image_de', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, ['gif', 'jpg', 'png'], true);

                if ($Ubanner_image_de->check()) {
                    $image = getimagesize($_FILES['banner_image_de']['tmp_name']);

                    if (($image !== false) && ($image[0] == '468') && ($image[1] == '60')) {
                        $Ubanner_image_de->setFilename($partner['code'] . '_banner-de.' . $Ubanner_image_de->getExtension());
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_image_de_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_image_de_error'));
                }
            }

            if (isset($_POST['banner_url_de'])) {
                $banner_url_de = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['banner_url_de']));

                if (strlen($banner_url_de) > 255) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_url_de_length'));
                } elseif (!empty($banner_url_de) && preg_match('/^(http|https)\:\/\/.+/', $banner_url_de) !== 1) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_banner_url_de_invalid'));
                } else {
                    $data['banner_url_de'] = $banner_url_de;
                }
            }

            if (isset($_POST['status_update_de'])) {
                $status_update_de = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['status_update_de']));

                if (strlen($status_update_de) > 200) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_status_update_de_length'));
                } else {
                    $data['status_update_de'] = $status_update_de;
                }
            }

            if (isset($_FILES['carousel_image']['name']) && !empty($_FILES['carousel_image']['name'])) {
                $Ucarousel_image = new Upload('carousel_image', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, ['jpg', 'png'], true);

                if ($Ucarousel_image->check()) {
                    $image = getimagesize($_FILES['carousel_image']['tmp_name']);

                    if (($image !== false) && ($image[0] == '1200') && ($image[1] == '364')) {
                        $Ucarousel_image->setFilename($partner['code'] . '_carousel.' . $Ucarousel_image->getExtension());
                    } else {
                        $error = true;

                        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_error'));
                    }
                } else {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_error'));
                }
            }

            if (isset($_POST['carousel_image_title'])) {
                $carousel_title = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['carousel_image_title']));

                if (strlen($carousel_title) > 255) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_title_length'));
                } else {
                    $data['carousel_title'] = $carousel_title;
                }
            }

            if (isset($_POST['carousel_image_url'])) {
                $carousel_url = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['carousel_image_url']));

                if (strlen($carousel_url) > 255) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_url_length'));
                } elseif (!empty($carousel_url) && preg_match('/^(http|https)\:\/\/.+/', $carousel_url) !== 1) {
                    $error = true;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_carousel_image_url_invalid'));
                } else {
                    $data['carousel_url'] = $carousel_url;
                }
            }
        }

        if ($error === false) {
            if (isset($_FILES['image_small']['name']) && !empty($_FILES['image_small']['name'])) {
                $Uimage_small->save();

                $data['image_small'] = $Uimage_small->getFilename();
            }

            if ($partner['has_gold'] == '1') {
                if (isset($_FILES['image_big']['name']) && !empty($_FILES['image_big']['name'])) {
                    $Uimage_big->save();

                    $data['image_big'] = $Uimage_big->getFilename();
                }

                if (isset($_FILES['image_promo']['name']) && !empty($_FILES['image_promo']['name'])) {
                    $Uimage_promo->save();

                    $data['image_promo'] = $Uimage_promo->getFilename();
                }

                if (isset($_FILES['banner_image_en']['name']) && !empty($_FILES['banner_image_en']['name'])) {
                    $Ubanner_image_en->save();

                    $data['banner_image_en'] = $Ubanner_image_en->getFilename();
                }

                if (isset($_FILES['banner_image_de']['name']) && !empty($_FILES['banner_image_de']['name'])) {
                    $Ubanner_image_de->save();

                    $data['banner_image_de'] = $Ubanner_image_de->getFilename();
                }

                if (isset($_FILES['carousel_image']['name']) && !empty($_FILES['carousel_image']['name'])) {
                    $Ucarousel_image->save();

                    $data['carousel_image'] = $Ucarousel_image->getFilename();
                }
            }

            if (Partner::save($_SESSION[OSCOM::getSite()]['Account']['id'], $partner['code'], $data)) {
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
                        $OSCOM_Mail = new Mail($admin['name'], $admin['email'], 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('email_partner_update_subject'));

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
