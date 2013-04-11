<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class PartnerSave {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $partner = array('desc_short' => $data['desc_short'],
                       'desc_long' => $data['desc_long'],
                       'address' => $data['address'],
                       'telephone' => $data['telephone'],
                       'email' => $data['email'],
                       'youtube_video_id' => $data['youtube_video_id'],
                       'url' => $data['url'],
                       'public_url' => $data['public_url'],
                       'image_promo_url' => $data['image_promo_url']);

      if ( isset($data['image_small']) ) {
        $partner['image_small'] = $data['image_small'];
      }

      if ( isset($data['image_big']) ) {
        $partner['image_big'] = $data['image_big'];
      }

      if ( isset($data['image_promo']) ) {
        $partner['image_promo'] = $data['image_promo'];
      } elseif ( !isset($partner['image_promo_url']) ) {
        $partner['image_promo'] = null;
      }

      $OSCOM_PDO->save('website_partner', $partner, array('id' => $data['id']));

      $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_banner where partner_id = :partner_id and code = "en"');
      $Qcheck->bindInt(':partner_id', $data['id']);
      $Qcheck->execute();

      if ( $Qcheck->fetch() !== false ) {
        if ( !isset($data['banner_url_en']) ) {
          $OSCOM_PDO->delete('website_partner_banner', array('id' => $Qcheck->valueInt('id')));
        } else {
          $banner = array('url' => $data['banner_url_en'],
                          'twitter' => $data['twitter_en']);

          if ( isset($data['banner_image_en']) ) {
            $banner['image'] = $data['banner_image_en'];
          }

          $OSCOM_PDO->save('website_partner_banner', $banner, array('id' => $Qcheck->valueInt('id')));
        }
      } elseif ( isset($data['banner_url_en']) ) {
        $banner = array('partner_id' => $data['id'],
                        'code' => 'en',
                        'url' => $data['banner_url_en'],
                        'twitter' => $data['twitter_en']);

        if ( isset($data['banner_image_en']) ) {
          $banner['image'] = $data['banner_image_en'];
        }

        $OSCOM_PDO->save('website_partner_banner', $banner);
      }

      $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_banner where partner_id = :partner_id and code = "de"');
      $Qcheck->bindInt(':partner_id', $data['id']);
      $Qcheck->execute();

      if ( $Qcheck->fetch() !== false ) {
        if ( !isset($data['banner_url_de']) ) {
          $OSCOM_PDO->delete('website_partner_banner', array('id' => $Qcheck->valueInt('id')));
        } else {
          $banner = array('url' => $data['banner_url_de'],
                          'twitter' => $data['twitter_de']);

          if ( isset($data['banner_image_de']) ) {
            $banner['image'] = $data['banner_image_de'];
          }

          $OSCOM_PDO->save('website_partner_banner', $banner, array('id' => $Qcheck->valueInt('id')));
        }
      } elseif ( isset($data['banner_url_de']) ) {
        $banner = array('partner_id' => $data['id'],
                        'code' => 'de',
                        'url' => $data['banner_url_de'],
                        'twitter' => $data['twitter_de']);

        if ( isset($data['banner_image_de']) ) {
          $banner['image'] = $data['banner_image_de'];
        }

        $OSCOM_PDO->save('website_partner_banner', $banner);
      }

      return true;
    }
  }
?>
