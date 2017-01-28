<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\HTML;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

use osCommerce\OM\Core\Site\Website\Partner;

class View
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['View']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['View'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner = Partner::get($_GET['View']);

        $OSCOM_Template->setValue('partner', $partner);

        $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['View']);

        $campaign = [
            'app_code' => $partner_campaign['app_code'],
            'info' => []
        ];

        foreach ($OSCOM_Language->getAll() as $l) {
            $info = Partner::getCampaignInfo($partner['id'], $l['id']);

            $campaign['info'][$l['code']] = [
                'title' => !empty($info['title']) ? $info['title'] : null,
                'desc_long' => !empty($info['desc_long']) ? nl2br($OSCOM_Template->parseContent($info['desc_long'], ['b', 'u', 'i', 'url', 'ul'])) : null,
                'url' => !empty($info['url']) ? $info['url'] : null,
                'public_url' => !empty($info['public_url']) ? $info['public_url'] : null,
                'email' => !empty($info['email']) ? $info['email'] : null,
                'telephone' => !empty($info['telephone']) ? $info['telephone'] : null,
                'address' => !empty($info['address']) ? nl2br($info['address']) : null,
                'image_small_filename' => !empty($info['image_small']) ? $info['image_small'] : null,
                'image_small' => null,
                'image_big_filename' => !empty($info['image_big']) ? $info['image_big'] : null,
                'image_big' => null,
                'youtube_video_id' => !empty($info['youtube_video_id']) ? $info['youtube_video_id'] : null
            ];
        }

        if (!empty($campaign['info']['en_US']['image_small_filename']) && file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/en_US/' . $campaign['info']['en_US']['image_small_filename'])) {
            $campaign['info']['en_US']['image_small'] = OSCOM::getPublicSiteLink('images/partners/en_US/' . $campaign['info']['en_US']['image_small_filename']);
        }

        unset($campaign['info']['en_US']['image_small_filename']);

        if (!empty($campaign['info']['en_US']['image_big_filename']) && file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/en_US/' . $campaign['info']['en_US']['image_big_filename'])) {
            $campaign['info']['en_US']['image_big'] = OSCOM::getPublicSiteLink('images/partners/en_US/' . $campaign['info']['en_US']['image_big_filename']);
        } else {
            $campaign['info']['en_US']['image_big'] = OSCOM::getPublicSiteLink($OSCOM_Template->getValue('highlights_image'));
        }

        unset($campaign['info']['en_US']['image_big_filename']);

        foreach ($campaign['info'] as $k => $v) {
            if ($k != 'en_US') {
                if (!empty($v['image_small_filename']) && file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $k . '/' . $v['image_small_filename'])) {
                    $campaign['info'][$k]['image_small'] = OSCOM::getPublicSiteLink('images/partners/' . $k . '/' . $v['image_small_filename']);
                }

                unset($campaign['info'][$k]['image_small_filename']);

                if (!empty($v['image_big_filename']) && file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $k . '/' . $v['image_big_filename'])) {
                    $campaign['info'][$k]['image_big'] = OSCOM::getPublicSiteLink('images/partners/' . $k . '/' . $v['image_big_filename']);
                }

                unset($campaign['info'][$k]['image_big_filename']);
            }
        }

        $OSCOM_Template->setValue('campaign_info', $campaign);

        $OSCOM_Template->setValue('language_code', $OSCOM_Language->getCode());

        $application->setPageContent('partner_preview.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner['title']
        ]));
    }
}
