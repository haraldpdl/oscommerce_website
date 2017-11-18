<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
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
    const COOKIE_MEMBER_ID = 'ips4_member_id';
    const COOKIE_PASS_HASH = 'ips4_pass_hash';

    const FORUM_ADDONS_CATEGORY_IDS = [
        51
    ];

    public static function fetchMember($search, $key)
    {
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

        if (in_array($key, ['email', 'username'])) {
            $OSCOM_IpbPdo = static::getIpbPdo();

            $sql = 'select member_id from :table_core_members where ';

            if ($key == 'email') {
                $sql .= 'email = :email';
            } else {
                $sql .= 'name = :name';
            }

            $sql .= ' limit 1';

            $Qm = $OSCOM_IpbPdo->prepare($sql);

            if ($key == 'email') {
                $Qm->bindValue(':email', $search);
            } else {
                $Qm->bindValue(':name', $search);
            }

            $Qm->execute();

            if (($Qm->fetch() !== false) && ($Qm->valueInt('member_id') > 0)) {
                $search = $Qm->valueInt('member_id');
            } else {
                return false;
            }
        }

        $fr_url = OSCOM::getConfig('forum_rest_url', 'Website');

        $url = $fr_url . 'core/members/' . (int)$search;

        $result = HttpRequest::getResponse([
            'url' => $url
        ]);

        if (!empty($result)) {
            $result = json_decode($result, true);

            if (is_array($result) && isset($result['id'])) {
                return static::getUserDataArray($result);
            }
        }

        return false;
    }

    public static function checkMemberExists($search, $key): bool
    {
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

        $OSCOM_IpbPdo = static::getIpbPdo();

        $sql = 'select member_id from :table_core_members where ';

        if ($key == 'id') {
            $sql .= 'member_id = :member_id';
        } elseif ($key == 'email') {
            $sql .= 'email = :email';
        } else {
            $sql .= 'name = :name';
        }

        $sql .= ' limit 1';

        $Qm = $OSCOM_IpbPdo->prepare($sql);

        if ($key == 'id') {
            $Qm->bindInt(':member_id', $search);
        } elseif ($key == 'email') {
            $Qm->bindValue(':email', $search);
        } else {
            $Qm->bindValue(':name', $search);
        }

        $Qm->execute();

        return $Qm->fetch() !== false;
    }

    public static function createUser($username, $email, $password)
    {
        if (empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            return false;
        }

        $fr_url = OSCOM::getConfig('forum_rest_url', 'Website');

        $url = $fr_url . 'core/members';

        $params = [
            'name' => $username,
            'email' => $email,
            'password' => $password,
            'group' => Users::GROUP_MEMBER_ID
        ];

        $result = HttpRequest::getResponse([
            'url' => $url,
            'parameters' => http_build_query($params, '', '&')
        ]);

        if (!empty($result)) {
            $result = json_decode($result, true);

            if (is_array($result) && isset($result['id'])) {
                return static::getUserDataArray($result);
            }
        }

        return false;
    }

    public static function verifyUserKey($user_id, $key)
    {
        if (!is_numeric($user_id) || ($user_id < 1)) {
            return false;
        }

        if (strlen($key) !== 32) {
            return false;
        }

        $user = static::fetchMember($user_id, 'id');

        if (($user !== false) && is_array($user) && isset($user['id']) && ($user['id'] > 0)) {
            if (($user['verified'] === false) && !empty($user['val_newreg_id'])) {
                if ($user['val_newreg_id'] == $key) {
/*
                    $fc_url = OSCOM::getConfig('forum_connect_url', 'Website');
                    $fc_key = OSCOM::getConfig('forum_connect_key', 'Website');

                    $params = [
                        'key' => md5($fc_key . $user['id']),
                        'do' => 'validate',
                        'id' => $user['id']
                    ];

                    $result = HttpRequest::getResponse([
                        'url' => $fc_url,
                        'parameters' => http_build_query($params, '', '&')
                    ]);

                    if (!empty($result)) {
                        $result = json_decode($result, true);

                        if (!empty($result) && is_array($result) && isset($result['status']) && ($result['status'] == 'SUCCESS')) {
                            return true;
                        }
                    }
*/

                    $fr_url = OSCOM::getConfig('forum_rest_url', 'Website');

                    $url = $fr_url . 'core/members/' . (int)$user['id'];

                    $params = [
                        'val_newreg_id' => 'clear'
                    ];

                    $result = HttpRequest::getResponse([
                        'url' => $url,
                        'parameters' => http_build_query($params, '', '&')
                    ]);

                    if (!empty($result)) {
                        $result = json_decode($result, true);

                        if (is_array($result) && isset($result['id'])) {
                            if ($result['validating'] === false) {
                                return true;
                            }
                        }
                    }
                } else {
                    return [
                        'error' => 'invalid_key'
                    ];
                }
            } else {
                return [
                    'error' => 'already_verified'
                ];
            }
        } else {
            return [
                'error' => 'invalid_member'
            ];
        }

        return false;
    }

    public static function getPasswordResetKey($user_id, bool $generate_new = false): array
    {
        $result = [];

        if (!is_numeric($user_id) || ($user_id < 1)) {
            $result['error'] = 'invalid_member';

            return $result;
        }

        $user = static::fetchMember($user_id, 'id');

        if (($user !== false) && is_array($user) && isset($user['id']) && ($user['id'] > 0)) {
            $send_email = true;

            $OSCOM_IpbPdo = static::getIpbPdo();

            $Qm = $OSCOM_IpbPdo->get('core_validating', [
                'vid',
                'email_sent'
            ], [
                'member_id' => $user['id'],
                'lost_pass' => 1
            ], null, 1);

            if ($Qm->fetch() !== false) {
                $vid = $Qm->value('vid');

                if ($Qm->hasValue('email_sent') && ($Qm->value('email_sent') > (time() - 900))) {
                    $send_email = false;
                } else {
                    $OSCOM_IpbPdo->save('core_validating', [
                        'email_sent' => time()
                    ], [
                        'vid' => $vid
                    ]);
                }
            } else {
                if ($generate_new === true) {
                    $vid = md5($user['login_key'] . Hash::getRandomString(16));

                    $OSCOM_IpbPdo->save('core_validating', [
                        'vid' => $vid,
                        'member_id' => $user['id'],
                        'entry_date' => time(),
                        'lost_pass' => 1,
                        'ip_address' => OSCOM::getIPAddress(),
                        'email_sent' => time()
                    ]);
                } else {
                    $result['error'] = 'not_found';
                }
            }

            if (isset($vid)) {
                $result['key'] = $vid;
                $result['id'] = $user['id'];
                $result['name'] = $user['name'];
                $result['email'] = $user['email'];
                $result['send_email'] = $send_email;
            }
        } else {
            $result['error'] = 'invalid_member';
        }

        return $result;
    }

    public static function deletePasswordResetKey($user_id): bool
    {
        if (is_numeric($user_id) && ($user_id > 0)) {
            $user = static::fetchMember($user_id, 'id');

            if (($user !== false) && is_array($user) && isset($user['id']) && ($user['id'] > 0)) {
                $OSCOM_IpbPdo = static::getIpbPdo();

                $result = $OSCOM_IpbPdo->delete('core_validating', [
                    'member_id' => $user_id,
                    'lost_pass' => 1
                ]);

                return $result === 1;
            }
        }

        return false;
    }

    public static function saveUser(int $id, array $data)
    {
        $params = [];

        if (isset($data['name'])) {
            $params['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $params['email'] = $data['email'];
        }

        if (isset($data['password'])) {
            $params['password'] = $data['password'];
        }

        if (isset($data['group'])) {
            $params['group'] = $data['group'];
        }

        if (isset($data['customFields'])) {
            $params['customFields'] = $data['customFields'];
        }

        if (!empty($params)) {
            $fr_url = OSCOM::getConfig('forum_rest_url', 'Website');

            $url = $fr_url . 'core/members/' . $id;

            $result = HttpRequest::getResponse([
                'url' => $url,
                'parameters' => http_build_query($params, '', '&')
            ]);

            if (!empty($result)) {
                $result = json_decode($result, true);

                if (is_array($result) && isset($result['id'])) {
                    return static::getUserDataArray($result);
                }
            }
        }

        return false;
    }

    public static function canLogin(string $username, string $password)
    {
        $OSCOM_IpbPdo = static::getIpbPdo();

        $check_email = false;

        $sql = 'select member_id, members_pass_hash, members_pass_salt from :table_core_members where ';

        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $check_email = true;

            $sql .= 'email = :email or ';
        }

        $sql .= 'name = :name limit 1';

        $Qm = $OSCOM_IpbPdo->prepare($sql);

        if ($check_email === true) {
            $Qm->bindValue(':email', $username);
        }

        $Qm->bindValue(':name', $username);
        $Qm->execute();

        if (($Qm->fetch() !== false) && ($Qm->valueInt('member_id') > 0)) {
            $using_legacy_password = false;

            if (strlen($Qm->value('members_pass_salt')) === 22) { // new password style
                $password_enc = static::getPasswordHash($Qm->value('members_pass_salt'), $password);
            } else { // legacy password style
                $using_legacy_password = true;

                $password_enc = static::getLegacyPasswordHash($Qm->value('members_pass_salt'), $password);
            }

            if ($password_enc == $Qm->value('members_pass_hash')) {
                $user = static::fetchMember($Qm->valueInt('member_id'), 'id');

                if (($user !== false) && is_array($user) && isset($user['id']) && ($user['id'] > 0)) {
                    if ($using_legacy_password === true) {
                        $pass_salt = Hash::getRandomString(22);
                        $pass_hash = static::getPasswordHash($pass_salt, $password);

                        $OSCOM_IpbPdo->save('core_members', [
                            'members_pass_salt' => $pass_salt,
                            'members_pass_hash' => $pass_hash
                        ], [
                            'member_id' => $Qm->valueInt('member_id')
                        ]);
                    }

                    return $user;
                }
            }
        }

        return false;
    }

    public static function canAutoLogin()
    {
        if (isset($_COOKIE[static::COOKIE_MEMBER_ID]) && is_numeric($_COOKIE[static::COOKIE_MEMBER_ID]) && ($_COOKIE[static::COOKIE_MEMBER_ID] > 0)) {
            if (isset($_COOKIE[static::COOKIE_PASS_HASH]) && (strlen($_COOKIE[static::COOKIE_PASS_HASH]) == 32)) {
                $OSCOM_IpbPdo = static::getIpbPdo();

                $Qm = $OSCOM_IpbPdo->prepare('select member_id, member_login_key from :table_core_members where member_id = :member_id');
                $Qm->bindInt(':member_id', $_COOKIE[static::COOKIE_MEMBER_ID]);
                $Qm->execute();

                if (($Qm->fetch() !== false) && ($Qm->valueInt('member_id') > 0) && !empty($Qm->value('member_login_key')) && ($Qm->value('member_login_key') === $_COOKIE[static::COOKIE_PASS_HASH])) {
                    return static::fetchMember($Qm->valueInt('member_id'), 'id');
                }

                static::killCookies();
            }
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

    public static function findMembers(string $search): array
    {
        $result = [];

        if (empty($search)) {
            return $result;
        }

        $OSCOM_IpbPdo = static::getIpbPdo();

        $Qm = $OSCOM_IpbPdo->prepare('select member_id as id, name from :table_core_members where name like :name and member_group_id not in (2, 5) order by name limit 5');
        $Qm->bindValue(':name', $search . '%');
        $Qm->execute();

        return $Qm->fetchAll();
    }

    public static function findMemberTopics(int $user_id, string $search, array $forum_filter = null): array
    {
        $result = [];

        if (empty($search)) {
            return $result;
        }

        $OSCOM_IpbPdo = static::getIpbPdo();

        $sql_query = 'select t.tid as id, t.title, t.title_seo, t.forum_id, l.word_default as forum_title from :table_forums_topics t, :table_core_sys_lang_words l where t.starter_id = :starter_id and t.title like :title and t.state = "open" ';

        if (!empty($forum_filter)) {
            $ids = [];

            foreach ($forum_filter as $filter) {
                if (is_numeric($filter) && !in_array((int)$filter, $ids)) {
                    $ids[] = (int)$filter;
                }
            }

            if (!empty($ids)) {
                $filter_ids = implode(', ', $ids);

                $sql_query .= <<<EOD
and t.forum_id in (
  select id from :table_forums_forums where parent_id in ({$filter_ids})
    UNION
      select id from :table_forums_forums where parent_id in (select id from :table_forums_forums where parent_id in ({$filter_ids}))
)
EOD;
            }
        }

        $sql_query .= ' and l.word_key = concat("forums_forum_", t.forum_id) order by title limit 5';

        $Qt = $OSCOM_IpbPdo->prepare($sql_query);
        $Qt->bindInt(':starter_id', $user_id);
        $Qt->bindValue(':title', '%' . $search . '%');
        $Qt->execute();

        $r = $Qt->fetchAll();

        if (!empty($r)) {
            $result = $r;
        }

        return $result;
    }

    public static function getMemberTopic(int $user_id, int $topic_id, array $forum_filter = null): array
    {
        $OSCOM_IpbPdo = static::getIpbPdo();

        $result = [];

        $sql_query = 'select t.tid as id, t.title, t.title_seo, t.forum_id, l.word_default as forum_title from :table_forums_topics t, :table_core_sys_lang_words l where t.tid = :tid and t.starter_id = :starter_id and t.state = "open" ';

        if (!empty($forum_filter)) {
            $ids = [];

            foreach ($forum_filter as $filter) {
                if (is_numeric($filter) && !in_array((int)$filter, $ids)) {
                    $ids[] = (int)$filter;
                }
            }

            if (!empty($ids)) {
                $filter_ids = implode(', ', $ids);

                $sql_query .= <<<EOD
and t.forum_id in (
  select id from :table_forums_forums where parent_id in ({$filter_ids})
    UNION
      select id from :table_forums_forums where parent_id in (select id from :table_forums_forums where parent_id in ({$filter_ids}))
)
EOD;
            }
        }

        $sql_query .= ' and l.word_key = concat("forums_forum_", t.forum_id)';

        $Qt = $OSCOM_IpbPdo->prepare($sql_query);
        $Qt->bindInt(':starter_id', $user_id);
        $Qt->bindInt(':tid', $topic_id);
        $Qt->execute();

        $r = $Qt->fetch();

        if (!empty($r)) {
            $result = $r;
        }

        return $result;
    }

    protected static function getIpbPdo(): PDO
    {
        if (!Registry::exists('IpbPdo')) {
            $OSCOM_IpbPdo = PDO::initialize(OSCOM::getConfig('forums_com_db_server', 'Website'), OSCOM::getConfig('forums_com_db_server_username', 'Website'), OSCOM::getConfig('forums_com_db_server_password', 'Website'), OSCOM::getConfig('forums_com_db_database', 'Website'), is_numeric(OSCOM::getConfig('forums_com_db_server_port', 'Website')) ? (int)OSCOM::getConfig('forums_com_db_server_port', 'Website') : null, OSCOM::getConfig('forums_com_db_driver', 'Website'));
            $OSCOM_IpbPdo->setTablePrefix(OSCOM::getConfig('forums_com_db_table_prefix', 'Website'));

            Registry::set('IpbPdo', $OSCOM_IpbPdo);
        }

        return Registry::get('IpbPdo');
    }

    protected static function getUserDataArray(array $member): array
    {
        return [
            'id' => (int)$member['id'],
            'name' => $member['name'],
            'formatted_name' => $member['formattedName'],
            'full_name' => $member['customFields'][2]['fields'][1]['value'],
            'title' => $member['title'],
            'email' => $member['email'],
            'group_id' => (int)$member['primaryGroup']['id'],
            'is_ambassador' => (int)$member['primaryGroup']['id'] === Users::GROUP_AMBASSADOR_ID,
            'amb_level' => (int)$member['customFields'][3]['fields'][23]['value'] ?? 0,
            'admin' => (int)$member['primaryGroup']['id'] === Users::GROUP_ADMIN_ID,
            'team' => in_array((int)$member['primaryGroup']['id'], [Users::GROUP_TEAM_CORE_ID, Users::GROUP_TEAM_COMMUNITY_ID]),
            'verified' => (bool)$member['validating'] === false,
            'banned' => (int)$member['temp_ban'] !== 0,
            'restricted_post' => ((int)$member['restrict_post'] !== 0) || ((int)$member['mod_posts'] !== 0),
            'login_key' => $member['member_login_key'],
            'joined' => $member['joined'],
            'posts' => (int)$member['posts'],
            'photo_url' => $member['photoUrl'],
            'val_newreg_id' => $member['val_newreg_id']
        ];
    }

    public static function killCookies()
    {
        if (isset($_COOKIE[static::COOKIE_MEMBER_ID])) {
            unset($_COOKIE[static::COOKIE_MEMBER_ID]);

            OSCOM::setCookie(static::COOKIE_MEMBER_ID, '', time() - 31536000, null, null, true, true);
        }

        if (isset($_COOKIE[static::COOKIE_PASS_HASH])) {
            unset($_COOKIE[static::COOKIE_PASS_HASH]);

            OSCOM::setCookie(static::COOKIE_PASS_HASH, '', time() - 31536000, null, null, true, true);
        }

    }

    public static function getTotalUsers(): int
    {
        $OSCOM_IpbPdo = static::getIpbPdo();

        $Qu = $OSCOM_IpbPdo->get('core_members', 'count(*) as total');

        return $Qu->valueInt('total');
    }

    public static function getTotalOnlineUsers(): int
    {
        $OSCOM_IpbPdo = static::getIpbPdo();

        $Qu = $OSCOM_IpbPdo->query('select count(*) as total from :table_core_sessions where running_time > unix_timestamp(date_sub(now(), interval 60 minute))');
        $Qu->execute();

        return $Qu->valueInt('total');
    }

    public static function getTotalPostings(): int
    {
        $OSCOM_IpbPdo = static::getIpbPdo();

        $Qp = $OSCOM_IpbPdo->query('select (select count(*) from :table_forums_posts) + (select count(*) from :table_forums_archive_posts) as total');
        $Qp->execute();

        return $Qp->valueInt('total');
    }
}
