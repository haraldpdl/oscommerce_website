<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Hash,
    HttpRequest,
    OSCOM,
    PDO,
    Registry
};

class Invision
{
    public static function fetchMember($search, $key)
    {
        $search = static::parseCleanValue($search);

        if (empty($search)) {
            return false;
        }

        if (!in_array($key, ['id', 'email', 'username'])) {
            return false;
        }

        if (($key == 'id') && !is_numeric($search)) {
            return false;
        }

        if (($key == 'email') && !filter_var($search, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $request = xmlrpc_encode_request('fetchMember', [
            'api_key' => OSCOM::getConfig('community_api_key'),
            'api_module' => OSCOM::getConfig('community_api_module'),
            'search_type' => $key,
            'search_string' => $search
        ], [
            'encoding' => 'utf-8'
        ]);

        $response = xmlrpc_decode(HttpRequest::getResponse([
            'url' => OSCOM::getConfig('community_api_address'),
            'parameters' => $request
        ]));

        return $response;
    }

    public static function checkMemberExists($search, $key): bool
    {
        if (empty($search)) {
            return false;
        }

        if (!in_array($key, ['email', 'username'])) {
            return false;
        }

        if (($key == 'email') && !filter_var($search, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $fc_url = OSCOM::getConfig('forum_connect_url', 'Website');
        $fc_key = OSCOM::getConfig('forum_connect_key', 'Website');

        $url = $fc_url . '?key=' . $fc_key . '&';

        if ($key == 'email') {
            $url .= 'do=checkEmail&email=' . rawurlencode($search);
        } else {
            $url .= 'do=checkName&name=' . rawurlencode($search);
        }

        $result = HttpRequest::getResponse([
            'url' => $url
        ]);

        if (!empty($result)) {
            $result = json_decode($result, true);

            if (!empty($result) && is_array($result) && isset($result['status']) && ($result['status'] == 'SUCCESS')) {
                if ($result['used'] == '1') {
                    return true;
                }
            }
        }

        return false;
    }

    public static function createUser($username, $email, $password)
    {
        $username = static::parseCleanValue($username);
        $email = static::parseCleanValue($email);
        $password = static::parseCleanValue($password);
        return false;

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $request = xmlrpc_encode_request('createUser', [
            'api_key' => OSCOM::getConfig('community_api_key'),
            'api_module' => 'oscommerce',
            'username' => $username,
            'email' => $email,
            'md5_pass' => md5($password),
            'ip' => OSCOM::getIPAddress()
        ], [
            'encoding' => 'utf-8'
        ]);

        $response = json_decode(HttpRequest::getResponse([
            'url' => OSCOM::getConfig('community_api_address'),
            'parameters' => $request
        ]), true);

        return $response;
    }

    public static function verifyUserKey($user_id, $key)
    {
        return false;

        $user_id = trim(str_replace(array("\r\n", "\n", "\r"), '', $user_id));
        $key = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $key);

        if (!is_numeric($user_id) || ($user_id < 1)) {
            return false;
        }

        if (strlen($key) !== 32) {
            return false;
        }

        $request = xmlrpc_encode_request('verifyUserKey', [
            'api_key' => OSCOM::getConfig('community_api_key'),
            'api_module' => 'oscommerce',
            'user_id' => $user_id,
            'key' => $key
        ], [
            'encoding' => 'utf-8'
        ]);

        $response = json_decode(HttpRequest::getResponse([
            'url' => OSCOM::getConfig('community_api_address'),
            'parameters' => $request
        ]), true);

        return $response;
    }

    public static function canLogin(string $username, string $password)
    {
        $fc_url = OSCOM::getConfig('forum_connect_url', 'Website');
        $fc_key = OSCOM::getConfig('forum_connect_key', 'Website');

        $result = HttpRequest::getResponse([
            'url' => $fc_url,
            'parameters' => 'key=' . md5($fc_key . $username) . '&do=fetchSalt&idType=3&id=' . $username
        ]);

        if (!empty($result)) {
            $result = json_decode($result, true);

            if (!empty($result) && is_array($result) && isset($result['status']) && ($result['status'] == 'SUCCESS')) {
                $using_legacy_password = false;

                if (strlen($result['pass_salt']) === 22) { // new password style
                    $password_enc = static::getPasswordHash($result['pass_salt'], $password);
                } else { // legacy password style
                    $using_legacy_password = true;

                    $password_enc = static::getLegacyPasswordHash($result['pass_salt'], $password);
                }

                $result = HttpRequest::getResponse([
                    'url' => $fc_url,
                    'parameters' => 'key=' . md5($fc_key . $username) . '&do=login&idType=3&id=' . $username . '&password=' . $password_enc
                ]);

                if (!empty($result)) {
                    $result = json_decode($result, true);

                    if (!empty($result) && is_array($result) && isset($result['status']) && ($result['status'] == 'SUCCESS')) {
                        if ($result['connect_status'] == 'SUCCESS') {
                            $fr_url = OSCOM::getConfig('forum_rest_url', 'Website');

                            $member_id = $result['connect_id'];

                            $url = $fr_url . 'core/members/' . (int)$member_id;

                            $result = HttpRequest::getResponse([
                                'url' => $url
                            ]);

                            if (!empty($result)) {
                                $result = json_decode($result, true);

                                if (is_array($result) && isset($result['id'])) {
                                    if ($using_legacy_password === true) {
                                        $pass_salt = Hash::getRandomString(22);
                                        $pass_hash = static::getPasswordHash($pass_salt, $password);

                                        HttpRequest::getResponse([
                                            'url' => $fc_url,
                                            'parameters' => 'key=' . md5($fc_key . $member_id) . '&do=changePassword&pass_salt=' . $pass_salt . '&pass_hash=' . $pass_hash . '&id=' . $member_id
                                        ]);
                                    }

                                    $user = [
                                        'member_id' => $result['id'],
                                        'members_display_name' => $result['name'],
                                        'email' => $result['email'],
                                        'member_group_id' => $result['primaryGroup']['id'],
                                        'temp_ban' => $result['temp_ban'],
                                        'restrict_post' => $result['restrict_post'],
                                        'mod_posts' => $result['mod_posts'],
                                        'member_login_key' => $result['member_login_key'],
                                        'members_pass_hash' => $result['members_pass_hash'],
                                        'members_pass_salt' => $result['members_pass_salt']
                                    ];

                                    return static::getUserDataArray($user);
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function canAutoLogin($id, $hash)
    {
        return false;

        $id = trim(str_replace(array("\r\n", "\n", "\r"), '', $id));
        $hash = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $hash);

        if (!is_numeric($id) || ($id < 1)) {
            return false;
        }

        if (strlen($hash) !== 32) {
            return false;
        }

        $OSCOM_IpbPdo = static::getIpbPdo();

        $Qm = $OSCOM_IpbPdo->prepare('select member_id, members_display_name, email, member_group_id, temp_ban, restrict_post, mod_posts, member_login_key from ibf_members where member_id = :member_id');
        $Qm->bindInt(':member_id', $id);
        $Qm->execute();

        if (($Qm->fetch() !== false) && ($Qm->valueInt('member_id') > 0) && !empty($Qm->value('member_login_key')) && ($Qm->value('member_login_key') === $hash)) {
            return static::getUserDataArray($Qm);
        }

        return false;
    }

    public static function getPasswordHash(string $salt, string $password): string
    {
        return crypt($password, '$2a$13$' . $salt);
    }

    public static function getLegacyPasswordHash(string $salt, string $password): string
    {
        return md5(md5($salt) . md5($password));
    }

    protected static function getIpbPdo(): \PDO
    {
        if (!Registry::exists('IpbPdo')) {
            $OSCOM_IpbPdo = PDO::initialize(OSCOM::getConfig('forums_com_db_server', 'Website'), OSCOM::getConfig('forums_com_db_server_username', 'Website'), OSCOM::getConfig('forums_com_db_server_password', 'Website'), OSCOM::getConfig('forums_com_db_database', 'Website'), is_numeric(OSCOM::getConfig('forums_com_db_server_port', 'Website')) ? (int)OSCOM::getConfig('forums_com_db_server_port', 'Website') : null, OSCOM::getConfig('forums_com_db_driver', 'Website'));

            Registry::set('IpbPdo', $OSCOM_IpbPdo);
        }

        return Registry::get('IpbPdo');
    }

    protected static function getUserDataArray(array $member): array
    {
        return [
            'id' => (int)$member['member_id'],
            'name' => $member['members_display_name'],
            'email' => $member['email'],
            'group_id' => $member['member_group_id'],
            'admin' => (int)$member['member_group_id'] === 4,
            'team' => in_array((int)$member['member_group_id'], [6, 19]),
            'verified' => (int)$member['member_group_id'] !== 1,
            'banned' => in_array((int)$member['member_group_id'], [2, 5]) || (!empty($member['temp_ban']) && ($member['temp_ban'] != '0')),
            'restricted_post' => (!empty($member['restrict_post']) && ($member['restrict_post'] != '0')) || (!empty($member['mod_posts']) && ($member['mod_posts'] != '0')),
            'login_key' => $member['member_login_key']
        ];
    }

    protected static function parseCleanValue($val): string
    {
        if (empty($val)) {
            return '';
        }

        $val = preg_replace('/\\\(?!&amp;#|\?#)/', '&#092;', $val);

        $val = str_replace('&#032;', ' ', $val);

        $val = str_replace(array("\r\n", "\n\r", "\r"), "\n", $val);

        $val = str_replace('&', '&amp;', $val);
        $val = str_replace('<!--', '&#60;&#33;--', $val);
        $val = str_replace('-->', '--&#62;', $val);
        $val = str_ireplace('<script', '&#60;script', $val);
        $val = str_replace('>', '&gt;', $val);
        $val = str_replace('<', '&lt;', $val);
        $val = str_replace('"', '&quot;', $val);
        $val = str_replace("\n", '<br />', $val);
        $val = str_replace('$', '&#036;', $val);
        $val = str_replace('!', '&#33;', $val);
        $val = str_replace("'", '&#39;', $val);

        $val = preg_replace('/&amp;#([0-9]+);/s', "&#\\1;", $val);
        $val = preg_replace('/&#(\d+?)([^\d;])/i', "&#\\1;\\2", $val);

        return $val;
    }
}
