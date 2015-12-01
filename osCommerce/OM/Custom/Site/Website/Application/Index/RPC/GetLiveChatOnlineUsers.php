<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\OSCOM;

class GetLiveChatOnlineUsers
{
    public static function execute()
    {
        $data = json_decode(file_get_contents('https://discordapp.com/api/servers/106369341515145216/widget.json'));

        $online = 0;

        foreach ($data->members as $m) {
            if (isset($m->status) && in_array($m->status, ['online', 'idle']) && ($m->username != 'bot')) {
                $online += 1;
            }
        }

        header('Cache-Control: max-age=1800, must-revalidate');
        header_remove('Pragma');
        header('Content-Type: application/javascript');

        $output = <<<JAVASCRIPT
var liveChatOnlineCounter = $online;

document.observe('dom:loaded', function() {
  if (liveChatOnlineCounter > 0) {
    $('chat-tab-count').innerHTML = parseInt(liveChatOnlineCounter);

    var _thisHtml = $('nav_app_discordchat').down('a').innerHTML;
    _thisHtml = _thisHtml + $('chat-tab-count-wrap').innerHTML;
    $('nav_app_discordchat').down('a').update( _thisHtml ).setStyle( { position: 'relative' } );
    $('chat-tab-count-wrap').remove();
    $('chat-tab-count').show();
  }
});
JAVASCRIPT;

        echo $output;
    }
}