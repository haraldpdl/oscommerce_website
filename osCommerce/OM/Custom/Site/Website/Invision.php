<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
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
        $search = static::parseCleanValue($search);

        if (empty($search)) {
            return false;
        }

        if (!in_array($key, ['email', 'username'])) {
            return false;
        }

        if (($key == 'email') && !filter_var($search, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $request = xmlrpc_encode_request('checkMemberExists', [
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

        return is_array($response) && isset($response['memberExists']) && ($response['memberExists'] === true);
    }

    public static function createUser($username, $email, $password)
    {
        $username = static::parseCleanValue($username);
        $email = static::parseCleanValue($email);
        $password = static::parseCleanValue($password);

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
        $OSCOM_IpbPdo = static::getIpbPdo();

        $username = static::parseCleanValue($username);
        $password = static::parseCleanValue($password);

        $Qm = $OSCOM_IpbPdo->prepare('select member_id, members_display_name, email, member_group_id, temp_ban, restrict_post, mod_posts, member_login_key, members_pass_hash, members_pass_salt from ibf_members where members_l_username = :members_l_username');
        $Qm->bindValue(':members_l_username', $username);
        $Qm->execute();

        if (($Qm->fetch() !== false) && ($Qm->valueInt('member_id') > 0) && ($Qm->value('members_pass_hash') === static::getPasswordHash($Qm->value('members_pass_salt'), $password))) {
            return static::getUserDataArray($Qm);
        }

        return false;
    }

    public static function canAutoLogin($id, $hash)
    {
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

    protected static function getUserDataArray(\PDOStatement $Qmember): array
    {
        return [
            'id' => $Qmember->valueInt('member_id'),
            'name' => $Qmember->value('members_display_name'),
            'email' => $Qmember->value('email'),
            'group_id' => $Qmember->valueInt('member_group_id'),
            'admin' => $Qmember->valueInt('member_group_id') === 4,
            'team' => in_array($Qmember->valueInt('member_group_id'), [6, 19]),
            'verified' => $Qmember->valueInt('member_group_id') !== 1,
            'banned' => in_array($Qmember->valueInt('member_group_id'), [2, 5]) || ($Qmember->hasValue('temp_ban') && !empty($Qmember->value('temp_ban')) && ($Qmember->value('temp_ban') != '0')),
            'restricted_post' => (!empty($Qmember->value('restrict_post')) && ($Qmember->value('restrict_post') != '0')) || (!empty($Qmember->value('mod_posts')) && ($Qmember->value('mod_posts') != '0')),
            'login_key' => $Qmember->value('member_login_key')
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
