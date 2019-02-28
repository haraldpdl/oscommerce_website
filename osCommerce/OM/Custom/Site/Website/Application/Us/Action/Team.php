<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Us\Action;

use osCommerce\OM\Core\{
  ApplicationAbstract,
  OSCOM,
  Registry
};

use osCommerce\OM\Core\Site\Website\Users;

use Cocur\Slugify\Slugify;

class Team
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        $team = [];

        $source = realpath(__DIR__ . '/../') . '/team.json';

        if (file_exists($source)) {
            $slugify = new Slugify();

            $workaholics = json_decode(file_get_contents($source));

            foreach ($workaholics[0] as $w) {
                $user = Users::get($w);

                $team[] = [
                    'name' => !empty($user['full_name']) ? $user['full_name'] : $user['name'],
                    'photo_url' => $user['photo_url'],
                    'profile_url' => 'https://forums.oscommerce.com/user/' . $user['id'] . '-' . $slugify->slugify($user['name']) . '/'
                ];
            }

            $community = [];

            foreach ($workaholics[1] as $w) {
                $user = Users::get($w);

                $community[] = [
                    'name' => !empty($user['full_name']) ? $user['full_name'] : $user['name'],
                    'photo_url' => $user['photo_url'],
                    'profile_url' => 'https://forums.oscommerce.com/user/' . $user['id'] . '-' . $slugify->slugify($user['name']) . '/'
                ];
            }

            usort($community, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            $team = array_merge($team, $community);
        }

        $OSCOM_Template->setValue('team_members', $team);

        $application->setPageContent('team.html');
        $application->setPageTitle(OSCOM::getDef('team_html_page_title'));
    }
}
