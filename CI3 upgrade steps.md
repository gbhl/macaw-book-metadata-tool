# CI3 upgrade steps

## Get new code

Run the appropriate Macaw update script, either `update-macaw.sh` or `Update-Macaw.ps1`

##  Move config files

Codeigniter moves the location of the application from `system/application/` to `application/`.
The new code is already there, but the config files won't move automaticallty and the old code
will still be there. A few steps must be done manually.

### Linux

```bash
cd /var/www/html

mv -i system/application/config/config.php application/config/.
mv -i system/application/config/database.php application/config/.
mv -i system/application/config/macaw.php application/config/.
mv -i system/application/logs application/.

# These should be empty
rmdir system/application/config
rmdir system/application/logs
rmdir system/application 
```

### Windows

```bat
cd C:\inetpub\wwwroot

move /-y system\application\config\config.php application\config
move /-y system\application\config\database.php application\config
move /-y system\application\config\macaw.php application\config
move /-y system\application\logs application

REM These should be empty
rmdir system\application\config
rmdir system\application\logs
rmdir system\application 
```

Later, if there are no errors, `system/application.OLD` can be deleted.

##  Config Settings

In `config.php` update session values to match these. Adjust 
accordingly, but remember that if `sess_time_to_update` is too 
low, the image upload process can get interrupted.

```php
$config['sess_driver']             = 'database';
$config['sess_cookie_name']        = 'macaw_session';
$config['sess_samesite']           = 'Lax'; 
$config['sess_expiration']         = 7200; # 2 hours
$config['sess_save_path']          = 'session';
$config['sess_match_ip']           = FALSE;
$config['sess_time_to_update']     = 72000; # less than 1 day
$config['sess_regenerate_destroy'] = FALSE;

$config['cookie_samesite'] 	= 'Strict';
```

##  Database Settings

In database.php update the database connection settings. These should be nearly identical to the existing settings

```php
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'	=> '',
    'hostname' => '127.0.0.1',
    'username' => '[MACAW_DB_USERNAME]',
    'password' => '[MACAW_DB_PASSWORD]',
    'database' => '[MACAW_DB_NAME]',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
```
## Macaw Config Settings

Update the `logs_directory` path to remove "system". Example

```php
# Before
$config['macaw']['logs_directory'] = $config['macaw']['base_directory']."/system/application/logs";
# After
$config['macaw']['logs_directory'] = $config['macaw']['base_directory']."/application/logs";
```
If there are other occurrences of `/system/application/`, replace with `/system/`.

##  Reset permissions

If necessary, reset permissions on the config files and the logs folder.

##  Upgrade 

Open the browser to: http://macaw.hostname.com/upgrade.php

This will update the session table, but it will invalidate all existing sessions.

