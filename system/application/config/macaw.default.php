<?php  if ( !defined('BASEPATH') && !defined('CRON')) exit('No direct script access allowed');

// MACAW CONFIGURATION FILE

// ------------------------------
// CURL EXECUTABLE
// ------------------------------
// Where is the CURL executable located? This is a hack until we can determine it
// for oursevles and is only used by the /cron/macaw_cron.php script.
$config['macaw']['curl_exe'] = "/usr/bin/curl";

// Where is the GhostScript (gs) executable located? This is a hack until we can determine it for ourselves.
$config['macaw']['gs_exe'] = "/usr/bin/gs";


// ------------------------------
// ADMINISTRATOR'S EMAIL
// ------------------------------
// Administrator email address. This person gets errors that are logged with a level
// of "info") and other emails that the system might send.
$config['macaw']['admin_email'] = "";

// ------------------------------
// ORGANIZATION NAME
// ------------------------------
// Tell Macaw who you are. This information is used when setting XMP metadata
// in the image files for an item.
$config['macaw']['organization_name'] = "";

// ------------------------------
// INSTALLATION PATHS
// ------------------------------
// Where do we store the files on the Macaw server? This is also the volume
// that is checked for the purge threshold. This must be an absolute path.
// The "BARCODE" in the path is replaced with the identifying barcode of the
// current book.

// Where is Macaw installed. What is the root web directory to macaw. This is
// likely going to be an /htdocs directory on your server.
//
// No trailing slashes! Absolute Path.
$config['macaw']['base_directory'] = "/path/to/webroot/htdocs";

// Where are the plugins found for this installation. This should be outside of
// the "system" folder.
$config['macaw']['plugins_directory'] = $config['macaw']['base_directory']."/plugins";

// In what directory do we store the books once they have been imported from
// the scanner. This is a path relative to the base_directory above. The reason
// for this is that this location must be web accessible since Macaw generates
// and serves the thumbnails and preview images from this location.
//
// The web server MUST have permissions to be able to read/write this location.
//
// No trailing slashes!
$config['macaw']['data_directory'] = $config['macaw']['base_directory']."/books";

// Where does Macaw store its logs. You shouldn't need to change this at all
// unless you know what you are doing.
//
// The web server MUST have permissions to be able to read/write this location.
//
// No trailing slashes!
$config['macaw']['logs_directory'] = $config['macaw']['base_directory']."/system/application/logs";

// ------------------------------
// LOGGING FILENAMES
// ------------------------------
// Names of the log files. You may use the following substitutions to have macaw automatically
// rotate the files if they begin to get too large:
//
// %Y - Four-digit year
// %m - Month number (01-12)
// %d - Day of the month (01-31)
// %H - 2-digit hour in 24-hour format (00-23)
//
// Other values from strftime() are allowed, but the string must translate to a valid
// filename for your system. i.e., colons and slashes will likely cause trouble.
$config['macaw']['access_log']   = 'macaw_access.%Y%m%d.log';
$config['macaw']['activity_log'] = 'macaw_activity.%Y%m%d.log';
$config['macaw']['error_log']    = 'macaw_error.%Y%m%d.log';
$config['macaw']['cron_log']     = 'macaw_cron.%Y%m%d.log';

// ------------------------------
// INCOMING FILES PATHS
// ------------------------------
// Where will Macaw look for new pages? This is called the "incoming" directory, but
// it can be called anything and be anywhere on your server with a few caveats
//
// First, it can't be the same as the base_directory. Macaw will strictly enforce this.
//
// Second, it must be accessible to those who will upload files to it, so this needs
// to be a world-writable directory (probably needs to be world-readable, too)
//
// Third, Macaw wants to keep this directory clean, too, so it's up to you to make
// sure that the web server has permissiosn to read/write/delete/rename from this
// location.
//
// Fourth, yes, this permission structure could be tricky. You may need a cron
// job to "chmod -R ugo+rwx /path/to/macaw/incoming"
//
// Lastly, this must be an absolute path on the server. How this is presented to
// other users is negotiable. (see the "incoming_directory_remote" setting below.)
//
// No trailing slashes!
$config['macaw']['incoming_directory'] = '';

// The "incoming_directory_remote" is a display-only value to indicate to the user
// how to connect to the server to place scans onto the server.
//
// This is a logical network location to the incoming folder and is only used
// for display purposes. It's up to the user to connect appropriately to the
// server in question. Examples of this might be:
//
//       FTP SERVER: ftp://www.server.com/macaw/incoming
//       Network Path: \\www.server.com\macaw\incoming
//       Unix Style SCP/SSH: www.server.com:/var/lib/macaw/incoming
//       Windows Style: N:\path\to\macaw\incoming
$config['macaw']['incoming_directory_remote'] = '';

// ------------------------------
// LOCATIONS OF PAGE IMAGES
// ------------------------------
// What is the relative URL to the full-size scans of a page? The value
// "BARCODE" in the path will be replaced automatically by Macaw with the
// identifying barcode of the current book. You should not need to change this
// value.
$config['macaw']['scans_url'] = "/books/BARCODE/scans";

// What is the relative URL to the preview images of a page? The value
// "BARCODE" in the path will be replaced automatically by Macaw with the
// identifying barcode of the current book. You should not need to change this
// value.
$config['macaw']['preview_url'] = "/books/BARCODE/preview";

// What is the relative URL to the thumbnail images of a page? The value
// "BARCODE" in the path will be replaced automatically by Macaw with the
// identifying barcode of the current book. You should not need to change this
// value.
$config['macaw']['thumbnail_url'] = "/books/BARCODE/thumbs";

// ------------------------------
// FILE FORMAT FOR PAGE IMAGES
// ------------------------------
// What file extensions do we use for the various formats of the file (thumb and preview)
$config['macaw']['thumbnail_format'] = 'jpg';
$config['macaw']['preview_format'] = 'jpg';

// ------------------------------
// LOG SETTINGS
// ------------------------------
// How long (days) do we wait before moving things into cool storage? And where
// is cool storage anyway? This process will be contingent upon validation that
// the harvest back from Internet Archive was successful. Also see the
// "purge_directory" setting to learn where things are placed after archiving.

// $config['macaw']['archive_wait']       = "30 days";
// $config['macaw']['archive_server']     = "172.120.234.34";
// $config['macaw']['archive_username']   = "bob";
// $config['macaw']['archive_password']   = "dot_matrix";
// $config['macaw']['archive_path']       = "/foobar/";

// Log level. How much do we want to see in the access logs?
//    debug – more information, could be spammy.
//    info  - high-level information (default)
$config['macaw']['log_level'] = "debug";

// ------------------------------
// PURGE SETTINGS
// ------------------------------
// At what point do we start deleting things from the /purge/ folder? If the %
// of available disk space is lower than the entered amount, things are deleted
// from /purge/ until we reach this threshold. 0.20 == 20%
//
// Be careful of this. Setting it too low will not leave enough space for new items.
// Setting to too high might not have an effect if things aren't being archived.
// This depends on the archive settings working properly.
// NOT USED YET
// $config['macaw']['purge_threshold'] = "0.20";

// Where do we look for things that can be purged? This is also used by the
// archiving process to place things that have been moved to cool storage.
// NOT USED YET
// $config['macaw']['purge_directory'] = $config['macaw']['base_directory']."/books/archived";

// ------------------------------
// EXPORT IDENTIFICATION
// ------------------------------
// Macaw is capable of exporting data to othher places via a variety of export modules.
// Each export module handles the creation and optional uploading of data into another
// system. The export modules can do pretty much whatever they want as long as they
// set the export status to "completed" when they're done.
//
// Each name listed here must correspond to a configuration file in the site's
// /plugins/export/ folder. For example we have this line configred here:
//
//    $config['macaw']['export_modules'] = array('Internet_Archive','SIL_DAMS');
//
// And therefore the following files must exist:
//    /plugins/export/Internet_Archive.php
//    /plugins/export/SIL_DAMS.php
//
// IMPORTANT NOTE: These are Case Sensitive! The configured names must be
// identical to that of the filenames.
//
// The format of these files is included in the sample file
//    /plugins/export/export.default.php
//
$config['macaw']['export_modules'] = array();

// We need to make sure that we pick up certain metadata fields before we can export to a partner
// (such as Internet Archive) to this end, we list out the metadata fieldnames that must exist
// before sending to the archive. The format of this is:
//
//     array('export_name' => array('fieldname_1','fieldname_2', ... 'fieldname_N');
//
// There is likely to be some overlap between this and the fields in "metadta_fields" below.
//
// When this is filled in, and the corresponding export module is enabled, a warning will be 
// presented on the main page for the item listing the fields that need to be provided. The name
// here must match exactly that of the "export_modules" above.
$config['macaw']['export_required_fields'] = array(
	'Internet_archive' => array('marc_xml', 'copyright', 'collection', 'year', 'title', 'sponsor')
);
$config['macaw']['export_optional_fields'] = array(
	'Internet_archive' => array('volume')
);

// ------------------------------
// IMPORT MODULES
// ------------------------------
//
// Similar to the configuration for export, these modules supply the basic metadata to create an
// item in the macaw database.
$config['macaw']['import_modules'] = array();

// ------------------------------
// METADATA FORMS
// ------------------------------
//
// These are always available to all users. The names here must correspond to the .JS and .PHP files
// that are created in the /plugins/metadata/ directory. The order of these is the order in which
// they will appear in the tabs on the page.

$config['macaw']['metadata_modules'] = array('Standard_Metadata');


// ------------------------------
// EMAIL CONFIGURATION
// ------------------------------
// Macaw sends email. Please tell us about your SMTP server. If these are
// blank, the QA feature and errors to admins will not work. Only a value for
// "email_smtp_host" is required. The others are optional and may be needed
// for email to work on your network. Email defaults to UTF-8.
$config['macaw']['email_smtp_host'] = "smtp.website.com";
$config['macaw']['email_smtp_port'] = "25";
$config['macaw']['email_smtp_user'] = "";
$config['macaw']['email_smtp_pass'] = "";


// ------------------------------
// METADATA FIELDS
// ------------------------------
// When getting a list of all items, which metadata fields should be returned (if they exist) 
// in the resulting dataset. This is only used in one place. Be careful of putting too many 
// items in this list as it may slow down certain procedures that use the full list of items.
$config['macaw']['metadata_fields'] = array('identifier', 'name', 'title', 'author');

// ------------------------------
// COPYRIGHT VALUES
// ------------------------------
// Possible values for the Copyright drop-down on the Add/Edit item page. You can override these
// with your own values, but keep in mind that the Internet_archive.php export is expecting values 
// of 0, 1, or 2 which it translates into values suitable for the Internet Archive
// If you want to change these, copy them into your macaw.php file. This is the default used if it 
// is not found. 
$config['macaw']['copyright_values'] = array(
	array('title' => 'Not in Copyright', 'value' => 0),
	array('title' => 'In Copyright, Permission Granted', 'value' => 1),
	array('title' => 'No known copyright, Due Diligence', 'value' => 2)
);

// ------------------------------
// CREATIVE COMMONS LICENSES
// ------------------------------
// For use when the item is in copyright but you want to set an explicit license, copy these into
// your macaw.php. These are the default values used by macaw and correspond to international 
// jurisdiction. You may want to change the URLs for your country, example:
//      
//      http://creativecommons.org/licenses/by/3.0/us/
//      http://creativecommons.org/licenses/by-sa/3.0/us/
//      http://creativecommons.org/licenses/by-nd/3.0/us/
//      http://creativecommons.org/licenses/by-nc/3.0/us/
//      http://creativecommons.org/licenses/by-nc-sa/3.0/us/
//      http://creativecommons.org/licenses/by-nc-nd/3.0/us/

$config['macaw']['cc_values'] = array(
	array('title' => '(none)', 			'value' => ''),
	array('title' => 'CC BY', 			'value' => 'http://creativecommons.org/licenses/by/3.0/'),
	array('title' => 'CC BY-SA', 		'value' => 'http://creativecommons.org/licenses/by-sa/3.0/'),
	array('title' => 'CC BY-ND', 		'value' => 'http://creativecommons.org/licenses/by-nd/3.0/'),
	array('title' => 'CC BY-NC', 		'value' => 'http://creativecommons.org/licenses/by-nc/3.0/'),
	array('title' => 'CC BY-NC-SA', 'value' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/'),
	array('title' => 'CC BY-NC-ND', 'value' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/'),
);

// ------------------------------
// JPEG 2000 Quality
// ------------------------------
// Used to override the default used in the Internet Archive export script.
// These default to values that are suitable for the OpenJPEG libraries used by ImageMagik
// version 6.8.8-2 or later. 
//
// The setting "jpeg2000_quality" is used for JP2 files created from other iamges, TIFF, PNG.
// The setting "jpeg2000_quality" is used for JP2 files created from PDFs uploaded to Macaw.
// $config['macaw']['jpeg2000_quality'] = '30';
// $config['macaw']['jpeg2000_quality_pdf'] = '32';

// For ImageMagick earlier than version 6.8.8-2, the Jasper Library is used and these values are more 
// appropriate.
// 
// $config['macaw']['jpeg2000_quality'] = '37';
// $config['macaw']['jpeg2000_quality_pdf'] = '50';

// ------------------------------
// BHL Connection
// ------------------------------
// In order to get the list of institutions for Rights Holder and Scanning Institution, you must set your
// BHL API Key here. If this is not set, the list of institutions will be empty. 
// $config['macaw']['bhl_api_key'] = 'API_KEY_GOES_HERE';

// ------------------------------
// Keep downloaded derivatves
// ------------------------------
// In the case of Macaw-in-the-Cloud for BHL we don't want to keep dervied files. It uses up a lot of space.
// If this is set to TRUE, the the export directory for the Internet Archive will be purged after the item is
// downloaded successfully. 
// $config['macaw']['purge_ia_deriatives'] = FALSE;

// ------------------------------
// Demo Organization
// ------------------------------
// Macaw likes to keep things clean. If this is set to the NAME of an orgnaization, all of the items for that
// organization will be purged nightly and replaced with a single test item. The name must match exactly 
// to the name of the organization in the /admin/organization page.
// $config['macaw']['demo_organization'] = '';

// ------------------------------
// TEST MODE?
// ------------------------------
// Used for only development servers. But since you don't know what it
// does. Leave it at zero. :)
$config['macaw']['testing'] = 0;

