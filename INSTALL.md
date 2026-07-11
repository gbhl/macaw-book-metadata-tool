
# Macaw Installation

## Overview

In the summer of 2010, the Smithsonian Institution Libraries, with a grant from the Atherton Seidell Endowment Fund, developed a process to scan folio volumes, large fold-outs, and other materials not suitable to our existing digitization workflow. As part of this process, the Macaw tool was developed to collect page-level metadata and manage the scanned pages. The result is a complete digital version of the item ready to be shared with external systems, such as the Biodiversity Heritage Library and the Internet Archive.

Installation of Macaw is pretty straightforward. Generally the procedure is as follows, assuming the system requirements are met. Details are below.

1. Install the required modules and components.
2. Download from github.
3. Unzip the file to the website directory that will contain Macaw.
4. Create a database to hold the Macaw data.
5. Open the install.php page in your web browser.

## System Requirements

Macaw runs on PHP and therefore needs a web server, too. PHP uses the CodeIgniter Framework, which is built in, so you don't need to install it. However, since Macaw does a variety of things, there are a few extra requirements. Hopefully none of these will be difficult to install on Linux. Most or all of them should be available in apt, aptitude or yum.

* Apache 2.4+
* Apache mod_rewrite
* PHP 8.2+
* PHP Archive_Tar support
* PHP XSL extension
* PHP iconv (not always installed with PHP on some systems)
* ImageMagick + OpenJPEG library
* cURL
* MySQL 8+ and the PHP mysqli extension or PostgreSQL 18+ (and the PHP pgsql extension)


## RedHat Enterprise Linux 8
```
# Needless to say this should all be done as root. 
sudo -s

# Don’t install weak dependencies, we don’t need them
alias dnf='dnf --setopt=install_weak_deps=False'

# Ensure we have the latest version of PHP
# Use the Remi RedHat Repository for PHP 8.2+ and related packages.
# Unstallation is left as an exercise to the reader
https://rpms.remirepo.net/

# Install the basic modules
dnf -y install httpd php82 php82-php-devel php82-php-pear make php82-php-cli php82-php-xml php82-php-mysqlnd php-json ImageMagick ImageMagick-devel openjpeg2 openjpeg2-tools curl unzip php82-pecl-zip mysql mysql-server ghostscript php82-php-fpm

# This comes from Pear
pear channel-update pear.php.net
pear install Archive_Tar

# ImageMagick integration is special
# /tmp and /var/tmp usually have ‘noexec’ set. 
# Use a different location for temporary files
mkdir ~/pecl-temp
pear config-set temp_dir ~/pecl-temp
pecl install imagick
echo "extension=imagick.so" >> /etc/php.d/20-imagick.ini
chmod a+r /etc/php.d/20-imagick.ini
rm -fr ~/pecl-temp

# Verification, should read “enabled”
php -i | fgrep 'imagick module'

# Start the services
systemctl enable httpd mysqld php82-php-fpm
systemctl start httpd mysqld php82-php-fpm

# Update the firewall
firewall-cmd --permanent --zone=public --add-service=http 
firewall-cmd --permanent --zone=public --add-service=https
firewall-cmd --reload

# This is a good idea. 
#   setup VALIDATE PASSWORD component... Yes
#   Remove anonymous users... Yes
#   Disallow root login remotely... Yes
#   Remove test database and access to it... Yes
#   Reload privilege tables now... Yes

mysql_secure_installation
```

## Windows IIS

All files, versions, and URLs listed are accurate at the time of writing. Later versions will need some attention to ensure that the non-thread-safe (nts) variant, Visual C++ version (vc##) and architecture (x64) are the same across packages. Use the non-thread-safe (nts) version of PHP for use in IIS.

Note: the highlighted values must match: ==php version, not-thread-safe (nts)==

1. Download PHP ([Download](https://www.php.net/downloads.php?os=windows), i.e. php-==8.2==.31-==nts==-Win32-vs16-x64.zip)
2. Configure PHP for Windows and IIS. At this time of writing, [this was a good tutorial](https://www.meersworld.net/2019/02/how-to-install-php-on-iis-in-windows-10.html).
3. Install ImageMagick ([Download](https://imagemagick.org/download/#windows), i.e. ImageMagick-7.1.2-21-Q16-HDRI-x64-dll.exe)
4. Download PHP Imagick ([Download](https://downloads.php.net/~windows/pecl/releases/imagick/), i.e. php_imagick-3.8.1-==8.2-nts==-vs16-x64.zip)
5. Download and Install Ghostscript AGPL Release, 64-bit ([Download](https://ghostscript.com/releases/gsdnld.html)) 
6. If necessary, install the [Microsoft Visual C++ Redistributable for Visual Studio 2019](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist?view=msvc-170)  Recent versions of PHP include it. Use `php -version` and look for “Visual C++ 2019”.
7. Ensure that the URL Rewrite Module is installed for IIS ([Download](https://www.iis.net/downloads/microsoft/url-rewrite) x64 installer)
8. Configure Imagick/ImageMagick. Again, [this was a good tutorial](https://herbmiller.me/installing-imagick-php-7/).
    * Ensure that the path to ImageMagic is set in the `%PATH%` environment variable before the path to PHP.
9. Install PEAR ([instructions](https://pear.php.net/manual/en/installation.getting.php)). The Archive_Tar module will be installed as part of this process.
10 Ensure the following extensions are enabled in PHP.INI:
    * php_imagick, zip, mbstring, zend_opcache, pdo_pgsql, pgsql, curl, xsl
11. Install MySQL ([download](https://dev.mysql.com/downloads/installer/)) or MariaDB ([download](https://mariadb.org/download)) or PostgreSQL ([download](https://www.postgresql.org/download/)). Create the macaw database and user with a strong password. Save these for later.
12. Navigate to http://localhost/install.php 

### Debian/Ubuntu

These instructions apply to Debian 12 (Bookworm) or later
```
# install modules with APT 
apt install apache2 php php-xsl php-mysql php-pear php-imagick php-zip php-dev php-mbstring imagemagick libopenjp2-7 curl unzip 

# install MySQL
apt install default-mysql-server default-mysql-client 

--OR--

# install PostgreSQL
apt install postgresql postgresql-client php-pgsql
```

## Disk Space Requirements

Macaw’s database requires very little disk space to start. A database containing almost 300,000 pages and 800 items is under 1GB of disk space. 
Since Macaw imports and processes large image files, much more disk space is needed to accommodate these. The exact size depends on your usage, but 100 GB is a good place to start. If images are being uploaded to the Internet Archive on a regular basis and the original images that Macaw uses are archived elsewhere, then Macaw’s image storage space can be purged from time to time. (Note: Let’s add an entry to the Developer’s guide on how to create a purge module)

## Downloading

The latest source code for Macaw is downloaded from GitHub:

https://github.com/gbhl/macaw-book-metadata-tool/archive/master.zip 

Uncompress the .zip file into an appropriate directory. The unzip process will make a directory called `/master/`. Move all of the files in `/master/` into the appropriate directory for your web server, including the `.htaccess` file. Macaw generally does well in the root directory, but efforts have been made to allow it to run under a subdirectory. Use with caution.

For the sake of discussion we will assume you are installing Macaw to `/var/www/html/`.
Instead of downloading the master.zip file, you can also change to the `/var/www/html` directory and clone the git repository:

```
git clone https://github.com/gbhl/macaw-book-metadata-tool.git .
```

## Installing

### Permissions

Make sure the web server user has write permissions to the correct directories using these commands:

```
cd /var/www/html
mkdir system/application/logs
mkdir system/application/logs/books
mkdir books
mkdir incoming

# remove default Apache HTML file, just in case
rm /var/www/html/index.html

# baseline restrictive permissions 
find . -type d -exec chmod 550 {} \;
find . -type f -exec chmod 440 {} \;

# grant permissions only where we need to write
chmod -R ug+w books/ incoming/ system/application/logs
chmod -R ug+w system/application/config

# ensure ownership. May also be “www-data” instead of “apache”
chown -R apache:apache . books/ incoming/ system/application/logs
chown -R apache:apache system/application/config
```

After you have completed the install.php process, you can remove the write permissions on the `/var/www/html/system/application/config` directory and the files it contains.

```
chmod -R ug-w system/application/config
```

### PHP Configuration

If you’re going to be uploading files to Macaw, you’ll need to change some settings in your php.ini file to account for files larger than the default 2MB that PHP allows. On Debian, the php.ini file is `/etc/php/8.x/apache2/php.ini`

Update the following settings in the php.ini file

```
upload_max_filesize = 256M
post_max_size = 256M
memory_limit = 256M
```

These can also be set in the Macaw `.htaccess` file if your web server allows it.

```
php_value upload_max_filesize 256M
php_value post_max_size 256M
php_value memory_limit 256M
```

### Apache Configuration

Macaw ships with a `.htaccess` file, but Apache still needs to be configured in order to use this file. To do this, you should include the `AllowOverride All` directive in the server configuration for the site. Below is a sample configuration for a virtual host for Macaw. You might need to make changes based on your server’s setup. 

```
<VirtualHost *:80>
  ServerAdmin webmaster@mycompany.com
  ServerName macaw.mycompany.com
  DocumentRoot /var/www/html
  <Directory /var/www/html>
    Options +FollowSymLinks -Indexes
    AllowOverride All
    Require all granted
  </Directory>
  ErrorLog /var/log/apache2/macaw-error.log
  CustomLog /var/log/apache2/macaw-access.log combined
</VirtualHost>
```

#### Apache mod_rewrite

Also, be sure that the rewrite module (`mod_rewrite`) is activated for your Apache installation. This will be different for your flavor of Linux. For example, on Debian and Ubuntu, we use the following:

```
cd /etc/apache2/mods-enabled/
ln -s ../mods-available/rewrite.load
```

Or

```
a2enmod rewrite
```

The remainder of the mod_rewrite configuration is handled by Macaw’s .htaccess file.

### Database Settings

You must also create the database user and database used by Macaw before starting the installer. The installer page cannot create the database for you. Macaw supports both MySQL and PostgreSQL.

#### MySQL

For MySQL, it’s easiest to first connect to MySQL from the command line and create the database that way. Be sure to replace password with the password you want to use for connecting to the database.

```
sudo mysql -u root -p mysql
CREATE DATABASE macaw CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'macaw'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON macaw.* To 'macaw'@'localhost';
```

#### PostgreSQL

The following pgsql commands can be used to create a database named “macaw” with an owner of “macaw”. Be sure to enter a password for the user. Note: you may need to update the `pg_hba.conf` file to allow “md5” authentication instead of “peer” authentication.

```
createuser -U postgres --createdb --no-createrole --no-superuser --pwprompt macaw
createdb -U macaw --encoding=UTF8 macaw
```

## Installation Page

Once the permissions and Apache configuration are complete, open to the install.php file for your installation in your browser. (e.g., http://macaw.mysite.com/install.php) This will start the installation which will walk you through a few steps to configure and prepare Macaw for use.

The installation page will verify that you have the necessary components for running Macaw. If those look good, then it will ask for the database configuration, including the database type, name, user and password that you set up earlier. If a component can’t be found or identified, you will be warned that it is missing or not found.

**Note:** The installation page checks for as many components as it can, but there may still be issues with the way it checks on different systems. This may cause errors down the line that need to be identified and resolved. 

Then Macaw will collect the administrator’s name and password and set that as the super user which has full access to all functions of Macaw. Save this password for later. If you lose it, it’s possible to reset the administrator password, but it requires a few steps on the command line, which is more difficult to get to. 

Finally, Macaw verifies that it has correct permissions to the files and directories it will be using. If those are correct, Macaw is ready to go.

## Configuration File

The `install.php` page creates and updates the following configuration files. The first two are specific to the CodeIgniter Framework. The last one is created specifically for Macaw and has a sightly different format to the variables it contains. 

### Global Configuration File
`/var/www/html/system/applications/config/config.php`

### Database Configuration File
`/var/www/html/system/applications/config/database.php`

### Macaw Configuration File
`/var/www/html/system/applications/config/macaw.php`

Most configuration settings are made for you during the installation process, but there may be some other items in the Macaw Configuration that you may want to change. Learn more about the various configuration file settings in the Macaw Developers Guide.

## Activating the Internet Archive Upload

**Note: This section is optional if you are not uploading to the Biodiversity Heritage Library.**

The automatic Internet Archive upload process for the BHL is enabled by default, but some configuration is necessary.

1. Verify the `plugins/export/Internet_archive.php` file exists.

2. Update the `system/application/config/macaw.php` configuration file to activate the export module

  `$config['macaw']['export_modules'] = array('Internet_Archive');`

3. Make sure the scheduled jobs are set up. (See the next section.)

4. Ensure that the API and Secret Keys are set in your Organization’s settings (See: Macaw Administrator’s Guide “Organizations and Contributors”)

**Note: if you want to test how the Internet Archive upload creates files, but not actually upload them to the Internet Archive, edit the `system/application/config/macaw.php` file and set the testing value to 1:**

`$config['macaw']['testing'] = 1;`

## Scheduled Jobs

Macaw runs best when there are scheduled cron entries to process completed items and export them to the Internet Archive or elsewhere. These processes are often time consuming and are best run in an unattended manner in the middle of the night. The following cron entries should be set up to enable Macaw to run correctly. They should be run as the web user (`www-data` or `apache`)

```
# Once per hour, export/verify/harvest/archive/etc
0 * * * *    /var/www/htdocs/cron.php --run=/cron/export > /dev/null 

# Once per day at 2:00am, update the Dashboard statistics
0 2 * * *    /var/www/htdocs/cron.php --run=/cron/statistics > /dev/null 
```

**Note:** the first entry is needed only if you are using an import module which needs to check for new items periodically. If items are only ever loaded manually or by CSV, this entry can be removed.

## Finished!

After this, Macaw should be successfully installed.
