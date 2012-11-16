osCommerce Website
==================

This repository contains the development of the new osCommerce website powered
by the osCommerce Online Merchant v3.0 framework.

Our website is not a general purpose solution for users to use as their
website - it is custom and tailored to run the main osCommerce website. The
frontend design is licensed and some graphics will not be available on Github.
No installation routine is present however setup instructions are available for
those interested in helping out.

The initial code is to propose template engine functionality to add to
osCommerce Online Merchant v3.0.

Installation
------------

Copy the "template" branch from my Github repository:

    git clone -b template https://github.com/haraldpdl/oscommerce.git

Copy this repository:

    git clone https://github.com/haraldpdl/oscommerce_website.git

The following directory structure is now in place:

* oscommerce - osCommerce Online Merchant v3.0
* oscommerce_website - osCommerce Website

Install osCommerce Online Merchant by visiting the following address:

    http://your-server/oscommerce/index.php?Setup

Symlink the following directories from "oscommerce_website" to "oscommerce":

    mkdir oscommerce/osCommerce/OM/Custom/Site
    cd oscommerce/osCommerce/OM/Custom/Site
    ln -s ../../../../../oscommerce_website/osCommerce/OM/Custom/Site/Website Website
    ln -s ../../../../../oscommerce_website/osCommerce/OM/Custom/Site/Admin Admin
    ln -s ../../../../../oscommerce_website/osCommerce/OM/Custom/Site/_skel _skel
    cd ..
    ln -s ../../../../oscommerce_website/osCommerce/OM/Custom/Exception Exception
    cd ..
    ln -s ../../../oscommerce_website/osCommerce/OM/External/simplepie_1.3.1.mini.php External/simplepie_1.3.1.mini.php
    cd ../../public/sites
    ln -s ../../../oscommerce_website/public/sites/Website Website
    cd ../external
    ln -s ../../../oscommerce_website/public/external/bootstrap bootstrap
    ln -s ../../../oscommerce_website/public/external/less less

A configuration block is also required in osCommerce/OM/Config/settings.ini,
which can be copied from an existing block:

    [Website]
    enable_ssl = "false"
    http_server = "http://your-server"
    https_server = "http://your-server"
    http_cookie_domain = ""
    https_cookie_domain = ""
    http_cookie_path = "/oscommerce/"
    https_cookie_path = "/oscommerce/"
    dir_ws_http_server = "/oscommerce/"
    dir_ws_https_server = "/oscommerce/"
    db_server = "localhost"
    db_server_username = "nobody"
    db_server_password = ""
    db_server_port = ""
    db_database = "oscommerce"
    db_driver = "MySQL\V5"
    db_table_prefix = "osc_"
    db_server_persistent_connections = "false"
    store_sessions = "Database"
    community_api_key = ""
    community_api_module = ""
    community_api_address = ""
    cron_key = ""
    save_user_server_info_salt = ""

The following SQL file needs to be imported into the installation database to
create the tables for the website:

    osCommerce/OM/Custom/Site/Website/Setup/SQL/mysql.sql

The website can then be accessed with the following URL:

    http://your-server/oscommerce/index.php?Website

The "Website" part in "index.php?Website" can be removed by making it the
default Site. In osCommerce/OM/Config/settings.ini change default_site in the
OSCOM group to "Website".

Feedback
---------

Please review the following forum topic for discussions on the template engine
functionality.

http://forums.oscommerce.com/topic/383392-template-engine-functionality-proposal/

Discussions for our new website platform are held in:

http://forums.oscommerce.com/forum/89-website-platform/

Note
----

Although the source code to new osCommerce website is available, it will
obviously not be packaged together with osCommerce Online Merchant v3.0.

Making the source code available serves to showcase the capabilities of the
framework, to migrate the Add-Ons and Live Shops sites to the new platform,
to create new Documentation and Translations Sites, and to kickstart the
initiative of a general purpose CMS Site that can be packaged with
osCommerce Online Merchant v3.0.
