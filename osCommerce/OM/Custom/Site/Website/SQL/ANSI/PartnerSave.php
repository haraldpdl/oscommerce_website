<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
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
                       'image_promo_url' => $data['image_promo_url'],
                       'carousel_title' => $data['carousel_title'],
                       'carousel_url' => $data['carousel_url']);

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

      if ( isset($data['carousel_image']) ) {
        $partner['carousel_image'] = $data['carousel_image'];
      }

      try {
        $OSCOM_PDO->beginTransaction();

        $OSCOM_PDO->save('website_partner', $partner, array('id' => $data['id']));

        $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_banner where partner_id = :partner_id and code = "en"');
        $Qcheck->bindInt(':partner_id', $data['id']);
        $Qcheck->execute();

        if ( $Qcheck->fetch() !== false ) {
          if ( !isset($data['banner_url_en']) ) {
            $OSCOM_PDO->delete('website_partner_banner', array('id' => $Qcheck->valueInt('id')));
          } else {
            $banner = array('url' => $data['banner_url_en']);

            if ( isset($data['banner_image_en']) ) {
              $banner['image'] = $data['banner_image_en'];
            }

            $OSCOM_PDO->save('website_partner_banner', $banner, array('id' => $Qcheck->valueInt('id')));
          }
        } elseif ( isset($data['banner_url_en']) ) {
          $banner = array('partner_id' => $data['id'],
                          'code' => 'en',
                          'url' => $data['banner_url_en']);

          if ( isset($data['banner_image_en']) ) {
            $banner['image'] = $data['banner_image_en'];
          }

          $OSCOM_PDO->save('website_partner_banner', $banner);
        }

        $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update where partner_id = :partner_id and code = "en"');
        $Qcheck->bindInt(':partner_id', $data['id']);
        $Qcheck->execute();

        if ( $Qcheck->fetch() !== false ) {
          if ( !isset($data['status_update_en']) ) {
            $OSCOM_PDO->delete('website_partner_status_update', array('id' => $Qcheck->valueInt('id')));
          } else {
            $status = array('status_update' => $data['status_update_en'],
                            'date_added' => 'now()');

            $OSCOM_PDO->save('website_partner_status_update', $status, array('id' => $Qcheck->valueInt('id')));
          }
        } elseif ( isset($data['status_update_en']) ) {
          $status = array('partner_id' => $data['id'],
                          'code' => 'en',
                          'status_update' => $data['status_update_en'],
                          'date_added' => 'now()');

          $OSCOM_PDO->save('website_partner_status_update', $status);
        }

        $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_banner where partner_id = :partner_id and code = "de"');
        $Qcheck->bindInt(':partner_id', $data['id']);
        $Qcheck->execute();

        if ( $Qcheck->fetch() !== false ) {
          if ( !isset($data['banner_url_de']) ) {
            $OSCOM_PDO->delete('website_partner_banner', array('id' => $Qcheck->valueInt('id')));
          } else {
            $banner = array('url' => $data['banner_url_de']);

            if ( isset($data['banner_image_de']) ) {
              $banner['image'] = $data['banner_image_de'];
            }

            $OSCOM_PDO->save('website_partner_banner', $banner, array('id' => $Qcheck->valueInt('id')));
          }
        } elseif ( isset($data['banner_url_de']) ) {
          $banner = array('partner_id' => $data['id'],
                          'code' => 'de',
                          'url' => $data['banner_url_de']);

          if ( isset($data['banner_image_de']) ) {
            $banner['image'] = $data['banner_image_de'];
          }

          $OSCOM_PDO->save('website_partner_banner', $banner);
        }

        $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update where partner_id = :partner_id and code = "de"');
        $Qcheck->bindInt(':partner_id', $data['id']);
        $Qcheck->execute();

        if ( $Qcheck->fetch() !== false ) {
          if ( !isset($data['status_update_de']) ) {
            $OSCOM_PDO->delete('website_partner_status_update', array('id' => $Qcheck->valueInt('id')));
          } else {
            $status = array('status_update' => $data['status_update_de'],
                            'date_added' => 'now()');

            $OSCOM_PDO->save('website_partner_status_update', $status, array('id' => $Qcheck->valueInt('id')));
          }
        } elseif ( isset($data['status_update_de']) ) {
          $status = array('partner_id' => $data['id'],
                          'code' => 'de',
                          'status_update' => $data['status_update_de'],
                          'date_added' => 'now()');

          $OSCOM_PDO->save('website_partner_status_update', $status);
        }

        return $OSCOM_PDO->commit();
      } catch ( \Exception $e ) {
        $OSCOM_PDO->rollBack();

        trigger_error($e->getMessage());
      }

      return false;
    }
  }
?>
