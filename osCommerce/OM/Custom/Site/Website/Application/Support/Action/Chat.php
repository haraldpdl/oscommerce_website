<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Support\Action;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\OSCOM;

class Chat
{
    public static function execute(ApplicationAbstract $application)
    {
        $application->setPageContent('chat.html');
        $application->setPageTitle(OSCOM::getDef('live_chat_html_page_title'));
    }
}
