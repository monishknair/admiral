Admiral
=================

Beautiful dashboards for tracking cross-browser end-to-end test results over time.

Based on the fantastic [TestSwarm](https://github.com/jquery/testswarm) project.

## Features

### High Level View: Results by Commit

Tracks cross-browser test results over time, pinpointing the commit or PR that introduced a failure.

![commits](https://cloud.githubusercontent.com/assets/1438478/8704138/6ce91d60-2ade-11e5-9225-88074ddbf3c0.gif)

### Drill-Down View: Individual Test Results

Investigate a specific build, to pinpoint which tests failed on which browsers.

![build2](https://cloud.githubusercontent.com/assets/1438478/8704279/41525594-2adf-11e5-89fd-4679f0e8263b.gif)

Quick start
----------

Clone the repo, `git clone --recursive git://github.com/TestArmada/admiral.git`.


Installation
-----------

### Environmental compatibility

To run Admiral you will need a web server, a database server and PHP.
At the moment Admiral supports the following, but other configurations
may work as well.

* Apache 2.0+, NGINX 1.2+
* PHP 5.3.2+ (or PHP-FPM for NGINX)
* MySQL 4.0+
* cURL (for the cleanup action; see step 8)

### Steps

1. Set up a MySQL database and create a user with read and write access.

1. Copy `config/sample-localSettings.php` to `config/localSettings.php`<br/>
   Copy `config/sample-localSettings.json` to `config/localSettings.json`.<br/>
   Edit `localSettings.json` and replace the sample settings with your own.<br/>
   Refer to the [Settings page](https://github.com/TestArmada/admiral/wiki/Settings) for more information.

1. *For Apache:*<br/>
   Copy `config/sample-.htaccess` to `.htaccess`.<br/>
   To run Admiral from a non-root directory, set `web.contextpath` in `localSettings.json` to the
   correct path from the web root and update RewriteBase in `.htaccess`.
   Verify that `.htaccess` is working properly by opening a page other than the HomePage (e.g.
   `/testswarm/projects`) in your browser.<br/>Required Apache configuration:<br/>
   * `AllowOverride` is set to `All` (or ensure `FileInfo` is included).
   * `mod_rewrite` installed and loaded.

   *For NGINX:*<br/>
   Copy `config/sample-nginx.conf` to `/etc/nginx/sites-available`.
   <br/>The file name should match your domain e.g. for swarm.example.org:<br/>
   `cp config/sample-nginx.conf /etc/nginx/sites-available/swarm.example.org.conf`
   <br/>Open this conf file in your editor and replace the "example" values with the correct values.
   <br/>Make sure your install is located at `/var/www/testswarm`
   (otherwise update the file to match the correct location).<br/>
   Now you need to link the `sites-available` config to the `sites-enabled` config:<br/>
   (replace the "swarm.example.org" with your own file name):<br/>
   `ln -s /etc/nginx/sites-available/swarm.example.org.conf /etc/nginx/sites-enabled/swarm.example.org.conf`<br/>
   Now make sure that php-fpm is running: `/etc/init.d/php-fpm status`<br/>
   if is not running start it: `/etc/init.d/php-fpm start`

1. Copy `config/sample-robots.txt` to `robots.txt`<br/>
   Or, if TestSwarm is not in the root directory, add similar rules to your root `robots.txt`.

1. Set `storage.cacheDir` to a writable directory that is not readable from the
   web. Either set it to a custom path outside the document root, or use the
   default `cache` directory (protected with .htaccess).<br/>Chmod it:
   `chmod 777 cache`.

1. Install the TestSwarm database by running:
   `php scripts/install.php`

1. Fetch the latest user-agent information:
   `php external/ua-parser/php/uaparser-cli.php -g`<br/>
   Note that ua-parser is based on patterns, so you don't need to re-run this
   after every browser release to be able to detect this, however it is recommmended
   to periodically run this to stay up to date (once a month should be enough).

1. Create an entry in your crontab for action=cleanup. This performs various
   cleaning duties such as making timed-out runs available again.<br/>
   `* * * * * curl -s http://swarm.example.org/api.php?action=cleanup > /dev/null`

1. [Create a project](./scripts/README.md#create-projects) and [submit jobs](./scripts/addjob/README.md).

Docker
---------------------
You can run Admiral in a Docker container, using the provided `Dockerfile`.  This assumes that you have an already existing MySQL instance.  If you don't and need help getting one setup, check out [Getting Started with MySQL](https://dev.mysql.com/usingmysql/get_started.html).

To build the container, use `docker build` passing in the MySQL information as build arguments, for example:

``` bash
❯ docker build --build-arg MYSQL_USER="user" --build-arg MYSQL_PASS="pass" --build-arg MYSQL_HOST="Your.db.fqdn" --build-arg MYSQL_DB="DB_name" -t testarmada/admiral
```

and then

```
❯ docker run -d -p 8080:80 --name admiral testaramda/admiral
```



Documentation
---------------------

* [TestSwarm wiki](https://github.com/jquery/testswarm/wiki)
* [Submit jobs README](https://github.com/jquery/testswarm/blob/master/scripts/addjob/README.md)
* [more wiki pages](https://github.com/jquery/testswarm/wiki/_pages)
* [Getting Started with MySQL](https://dev.mysql.com/usingmysql/get_started.html)


Copyright and license
---------------------

See [LICENSE.txt](https://raw.github.com/jquery/testswarm/master/LICENSE.txt).


History
---------------------

TestSwarm was originally created by [John Resig](http://ejohn.org/) as a
basic tool to support unit testing of the [jQuery JavaScript
library](http://jquery.com). It was later moved to become an official
[Mozilla Labs](http://labs.mozilla.com/) and has since moved again to become
a [jQuery](http://jquery.org/) project.
