# osCommerce Website
#
# @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
# @license BSD License; http://www.oscommerce.com/bsdlicense.txt

DROP TABLE IF EXISTS osc_website_downloads;
CREATE TABLE osc_website_downloads (
  id int unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  filename varchar(255) NOT NULL,
  counter int unsigned DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_downloads_log;
CREATE TABLE osc_website_downloads_log (
  id int unsigned NOT NULL,
  date_added datetime NOT NULL,
  ip_address int unsigned NOT NULL,
  KEY idx_ws_downloads_log_id (id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_news;
CREATE TABLE osc_website_news (
  id int unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  body text NOT NULL,
  date_added datetime NOT NULL,
  status int NOT NULL,
  image varchar(255),
  author_id int unsigned,
  PRIMARY KEY (id),
  KEY idx_ws_news_title (title),
  KEY idx_ws_news_date_added (date_added),
  KEY idx_ws_news_status (status),
  KEY idx_ws_news_author_id (author_id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_partner_category;
CREATE TABLE osc_website_partner_category (
  id int unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  code varchar(255) NOT NULL,
  sort_order int unsigned,
  PRIMARY KEY (id),
  KEY idx_ws_partner_category_title (title),
  KEY idx_ws_partner_category_code (code),
  KEY idx_ws_partner_category_sort_order (sort_order)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

insert into osc_website_partner_category values (1, 'Hosting', 'hosting', 100);
insert into osc_website_partner_category values (2, 'Developers', 'developers', 200);
insert into osc_website_partner_category values (3, 'Payment', 'payment', 300);
insert into osc_website_partner_category values (4, 'Templates', 'templates', 400);
insert into osc_website_partner_category values (5, 'Security', 'security', 500);
insert into osc_website_partner_category values (6, 'Marketing', 'marketing', 600);

DROP TABLE IF EXISTS osc_website_partner_package;
CREATE TABLE osc_website_partner_package (
  id int unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  code varchar(255) NOT NULL,
  status int,
  sort_order int,
  PRIMARY KEY (id),
  KEY idx_ws_partner_package_title (title),
  KEY idx_ws_partner_package_code (code),
  KEY idx_ws_partner_package_status (status)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

insert into osc_website_partner_package values (1, 'Bronze Level', 'bronze', 0, 1);
insert into osc_website_partner_package values (2, 'Silver Level', 'silver', 1, 2);
insert into osc_website_partner_package values (3, 'Gold Level', 'gold', 1, 3);

DROP TABLE IF EXISTS osc_website_partner;
CREATE TABLE osc_website_partner (
  id int unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  code varchar(255) NOT NULL,
  category_id int unsigned NOT NULL,
  desc_short text,
  desc_long text,
  address text,
  telephone varchar(255),
  email varchar(255),
  url varchar(255),
  public_url varchar(255),
  image_small varchar(255),
  image_big varchar(255),
  image_promo varchar(255),
  image_promo_url varchar(255),
  youtube_video_id varchar(255),
  PRIMARY KEY (id),
  KEY idx_ws_partner_title (title),
  KEY idx_ws_partner_code (code),
  KEY idx_ws_partner_category_id (category_id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_partner_account;
CREATE TABLE osc_website_partner_account (
  community_account_id int unsigned NOT NULL,
  partner_id int unsigned NOT NULL,
  PRIMARY KEY (community_account_id, partner_id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_partner_banner;
CREATE TABLE osc_website_partner_banner (
  id int unsigned NOT NULL AUTO_INCREMENT,
  partner_id int unsigned NOT NULL,
  code varchar(255) NOT NULL,
  image varchar(255),
  url varchar(255),
  twitter varchar(255),
  PRIMARY KEY (id),
  KEY idx_ws_partner_banner_partner_id (partner_id),
  KEY idx_ws_partner_banner_code (code)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_partner_transaction;
CREATE TABLE osc_website_partner_transaction (
  id int unsigned NOT NULL AUTO_INCREMENT,
  partner_id int unsigned NOT NULL,
  package_id int unsigned NOT NULL,
  date_added datetime,
  date_start datetime,
  date_end datetime,
  cost decimal(8,2),
  PRIMARY KEY (id),
  KEY idx_ws_partner_tx_partner_id (partner_id),
  KEY idx_ws_partner_tx_package_id (package_id),
  KEY idx_ws_partner_tx_date_start (date_start),
  KEY idx_ws_partner_tx_date_end (date_end)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_partner_status_update;
CREATE TABLE osc_website_partner_status_update (
  id int unsigned NOT NULL AUTO_INCREMENT,
  partner_id int unsigned NOT NULL,
  code varchar(255) NOT NULL,
  status_update text,
  date_added datetime,
  PRIMARY KEY (id),
  KEY idx_ws_partner_su_partner_id (partner_id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_partner_status_update_urls;
CREATE TABLE osc_website_partner_status_update_urls (
  id char(8) NOT NULL,
  partner_id int unsigned NOT NULL,
  url varchar(255) NOT NULL,
  date_added datetime,
  PRIMARY KEY (id),
  KEY idx_ws_partner_su_u_partner_id (partner_id),
  KEY idx_ws_partner_su_u_url (url)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_user_server_info;
CREATE TABLE osc_website_user_server_info (
  id int unsigned NOT NULL AUTO_INCREMENT,
  submit_ip varchar(255),
  osc_version varchar(255),
  system_os varchar(255),
  http_server varchar(255),
  php_version varchar(255),
  php_extensions text,
  php_sapi varchar(255),
  php_memory varchar(255),
  mysql_version varchar(255),
  php_other text,
  system_other text,
  mysql_other text,
  date_added datetime,
  date_updated datetime,
  update_count int DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_ws_user_server_info_ip (submit_ip)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS osc_website_user_profiles;
CREATE TABLE osc_website_user_profiles (
  id int unsigned NOT NULL,
  display_name varchar(255) NOT NULL,
  twitter_id varchar(255),
  google_plus_id varchar(255),
  facebook_id varchar(255),
  github_id varchar(255),
  PRIMARY KEY (id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;
