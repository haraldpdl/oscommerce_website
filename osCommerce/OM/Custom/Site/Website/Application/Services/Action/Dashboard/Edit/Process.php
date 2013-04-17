<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Services\Action\Dashboard\Edit;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Upload;

  use osCommerce\OM\Core\Site\Website\Partner;

  class Process {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_MessageStack = Registry::get('MessageStack');
      $OSCOM_Template = Registry::get('Template');

      $partner = $OSCOM_Template->getValue('partner_campaign');

      $data = array();
      $error = false;

      if ( !isset($_POST['desc_short']) || empty($_POST['desc_short']) ) {
        $error = true;

        $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_desc_short_empty'));
      } else {
        $desc_short = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['desc_short']));

        if ( strlen($desc_short) > 450 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_desc_short_length'));
        } else {
          $data['desc_short'] = $desc_short;
        }
      }

      if ( !isset($_POST['desc_long']) || empty($_POST['desc_long']) ) {
        $error = true;

        $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_desc_long_empty'));
      } else {
        $desc_long = trim($_POST['desc_long']);

        $data['desc_long'] = $desc_long;
      }

      if ( isset($_POST['address']) ) {
        $address = trim($_POST['address']);

        if ( strlen($address) > 255 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_address_length'));
        } else {
          $data['address'] = !empty($address) ? $address : null;
        }
      }

      if ( isset($_POST['telephone']) ) {
        $telephone = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['telephone']));

        if ( strlen($telephone) > 255 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_telephone_length'));
        } else {
          $data['telephone'] = !empty($telephone) ? $telephone : null;
        }
      }

      if ( isset($_POST['email']) ) {
        $email = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['email']));

        if ( !empty($email) && (filter_var($email, FILTER_VALIDATE_EMAIL) === false) || (strlen($email) > 255) ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_email_length'));
        } else {
          $data['email'] = !empty($email) ? $email : null;
        }
      }

      if ( isset($_POST['youtube_video_id']) ) {
        $youtube_video_id = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['youtube_video_id']));

        if ( strlen($youtube_video_id) > 255 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_youtube_video_id_length'));
        } else {
          $curl = curl_init('https://gdata.youtube.com/feeds/api/videos/' . $youtube_video_id);

          $curl_options = array(CURLOPT_HEADER => true,
                                CURLOPT_SSL_VERIFYPEER => true,
                                CURLOPT_SSL_VERIFYHOST => 2,
                                CURLOPT_NOBODY => true,
                                CURLOPT_FORBID_REUSE => true,
                                CURLOPT_FRESH_CONNECT => true,
                                CURLOPT_FOLLOWLOCATION => false);

          curl_setopt_array($curl, $curl_options);
          $result = curl_exec($curl);

          if ( $result !== false ) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ( $http_code !== 200 ) {
              $error = true;

              $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_youtube_video_id_invalid'));
            } else {
              $data['youtube_video_id'] = !empty($youtube_video_id) ? $youtube_video_id : null;
            }
          } else {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_youtube_video_id_invalid'));
          }
        }
      }

      if ( !isset($_POST['public_url']) || empty($_POST['public_url']) ) {
        $error = true;

        $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_public_url_empty'));
      } else {
        $public_url = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_url']));

        if ( strlen($public_url) > 255 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_public_url_length'));
        } else {
          $data['public_url'] = $public_url;
        }
      }

      if ( !isset($_POST['url']) || empty($_POST['url']) ) {
        $error = true;

        $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_url_empty'));
      } else {
        $url = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['url']));

        if ( strlen($url) > 255 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_url_length'));
        } else {
          $data['url'] = $url;
        }
      }

      if ( isset($_FILES['image_small']['name']) && !empty($_FILES['image_small']['name']) ) {
        $Uimage_small = new Upload('image_small', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, array('jpg', 'png'), true);
  
        if ( $Uimage_small->check() ) {
          $image = getimagesize($_FILES['image_small']['tmp_name']);

          if ( ($image !== false) && ($image[0] == '130') && ($image[1] == '50') ) {
            $Uimage_small->setFilename($partner['code'] . '.' . $Uimage_small->getExtension());
          } else {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_small_error'));
          }
        } else {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_small_error'));
        }
      }

      if ( $partner['has_gold'] == '1' ) {
        if ( isset($_FILES['image_big']['name']) && !empty($_FILES['image_big']['name']) ) {
          $Uimage_big = new Upload('image_big', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, array('jpg', 'png'), true);
  
          if ( $Uimage_big->check() ) {
            $image = getimagesize($_FILES['image_big']['tmp_name']);

            if ( ($image !== false) && ($image[0] == '940') && ($image[1] == '285') ) {
              $Uimage_big->setFilename($partner['code'] . '_header.' . $Uimage_big->getExtension());
            } else {
              $error = true;

              $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_big_error'));
            }
          } else {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_big_error'));
          }
        }

        if ( isset($_FILES['image_promo']['name']) && !empty($_FILES['image_promo']['name']) ) {
          $Uimage_promo = new Upload('image_promo', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, array('gif', 'jpg', 'png'), true);
  
          if ( $Uimage_promo->check() ) {
            $image = getimagesize($_FILES['image_promo']['tmp_name']);

            if ( ($image !== false) && ($image[0] == '150') && ($image[1] == '100') ) {
              $Uimage_promo->setFilename($partner['code'] . '_promo.' . $Uimage_promo->getExtension());
            } else {
              $error = true;

              $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_promo_error'));
            }
          } else {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_promo_error'));
          }
        }

        if ( isset($_POST['image_promo_url']) ) {
          $image_promo_url = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['image_promo_url']));

          if ( strlen($image_promo_url) > 255 ) {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_image_promo_url_length'));
          } else {
            $data['image_promo_url'] = !empty($image_promo_url) ? $image_promo_url : null;
          }
        }

        if ( isset($_FILES['banner_image_en']['name']) && !empty($_FILES['banner_image_en']['name']) ) {
          $Ubanner_image_en = new Upload('banner_image_en', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, array('gif', 'jpg', 'png'), true);
  
          if ( $Ubanner_image_en->check() ) {
            $image = getimagesize($_FILES['banner_image_en']['tmp_name']);

            if ( ($image !== false) && ($image[0] == '468') && ($image[1] == '60') ) {
              $Ubanner_image_en->setFilename($partner['code'] . '_banner.' . $Ubanner_image_en->getExtension());
            } else {
              $error = true;

              $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_banner_image_en_error'));
            }
          } else {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_banner_image_en_error'));
          }
        }

        if ( isset($_POST['banner_url_en']) ) {
          $banner_url_en = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['banner_url_en']));

          if ( strlen($banner_url_en) > 255 ) {
            $error = true;

            $OSCOM_MessageStack->add('services',  OSCOM::getDef('dashboard_error_banner_url_en_length'));
          } else {
            $data['banner_url_en'] = !empty($banner_url_en) ? $banner_url_en : null;
          }
        }

        if ( isset($_POST['twitter_en']) ) {
          $twitter_en = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['twitter_en']));

          if ( strlen($twitter_en) > 255 ) {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_twitter_en_length'));
          } else {
            $data['twitter_en'] = !empty($twitter_en) ? $twitter_en : null;
          }
        }

        if ( isset($_POST['status_update_en']) ) {
          $status_update_en = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['status_update_en']));

          if ( strlen($status_update_en) > 200 ) {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_status_update_en_length'));
          } else {
            $data['status_update_en'] = !empty($status_update_en) ? $status_update_en : null;
          }
        }

        if ( isset($_FILES['banner_image_de']['name']) && !empty($_FILES['banner_image_de']['name']) ) {
          $Ubanner_image_de = new Upload('banner_image_de', OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/images/partners', null, array('gif', 'jpg', 'png'), true);
  
          if ( $Ubanner_image_de->check() ) {
            $image = getimagesize($_FILES['banner_image_de']['tmp_name']);

            if ( ($image !== false) && ($image[0] == '468') && ($image[1] == '60') ) {
              $Ubanner_image_de->setFilename($partner['code'] . '_banner-de.' . $Ubanner_image_de->getExtension());
            } else {
              $error = true;

              $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_banner_image_de_error'));
            }
          } else {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_banner_image_de_error'));
          }
        }

        if ( isset($_POST['banner_url_de']) ) {
          $banner_url_de = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['banner_url_de']));

          if ( strlen($banner_url_de) > 255 ) {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_banner_url_de_length'));
          } else {
            $data['banner_url_de'] = !empty($banner_url_de) ? $banner_url_de : null;
          }
        }

        if ( isset($_POST['twitter_de']) ) {
          $twitter_de = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['twitter_de']));

          if ( strlen($twitter_de) > 255 ) {
            $error = true;

            $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_twitter_de_length'));
          } else {
            $data['twitter_de'] = !empty($twitter_de) ? $twitter_de : null;
          }
        }
      }

      if ( isset($_POST['status_update_de']) ) {
        $status_update_de = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['status_update_de']));

        if ( strlen($status_update_de) > 200 ) {
          $error = true;

          $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_error_status_update_de_length'));
        } else {
          $data['status_update_de'] = !empty($status_update_de) ? $status_update_de : null;
        }
      }

      if ( $error === false ) {
        if ( isset($_FILES['image_small']['name']) && !empty($_FILES['image_small']['name']) ) {
          $Uimage_small->save();

          $data['image_small'] = $Uimage_small->getFilename();
        }

        if ( $partner['has_gold'] == '1' ) {
          if ( isset($_FILES['image_big']['name']) && !empty($_FILES['image_big']['name']) ) {
            $Uimage_big->save();

            $data['image_big'] = $Uimage_big->getFilename();
          }

          if ( isset($_FILES['image_promo']['name']) && !empty($_FILES['image_promo']['name']) ) {
            $Uimage_promo->save();

            $data['image_promo'] = $Uimage_promo->getFilename();
          }

          if ( isset($_FILES['banner_image_en']['name']) && !empty($_FILES['banner_image_en']['name']) ) {
            $Ubanner_image_en->save();

            $data['banner_image_en'] = $Ubanner_image_en->getFilename();
          }

          if ( isset($_FILES['banner_image_de']['name']) && !empty($_FILES['banner_image_de']['name']) ) {
            $Ubanner_image_de->save();

            $data['banner_image_de'] = $Ubanner_image_de->getFilename();
          }
        }

        Partner::save($_SESSION[OSCOM::getSite()]['Services']['id'], $partner['code'], $data);

        $OSCOM_MessageStack->add('services', OSCOM::getDef('dashboard_success_save', array(':partner_link' => OSCOM::getLink(null, 'Services', $partner['category_code'] . '&' . $partner['code']))), 'success');

        OSCOM::redirect(OSCOM::getLink(null, 'Services', 'Dashboard&Edit=' . $partner['code']));
      }
    }
  }
?>
