<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
* ------------------------
* Config: set home
* ------------------------
* Default value:
* $config['set_home'] = "Home";
*
* Change initial breadcrumb link
*/
$config['set_home'] = "Home";

/*
* ------------------------
* Config: Delimiter
* ------------------------
* Default value:
* $config['delimiter'] = ' > ';
*/
$config['delimiter'] = '<li class="sep"> &gt; </li>';

/*
 * --------------------------
 * Config: Replacer
 * --------------------------
 * Default value:
 * $config['replacer'] = array();
 *
 * Replacer have some usefull function, e.g:
 *
 * 1. Change link label
 *
 * Example:
 * =======
 * URL = http://localhost/arstock/warehouse/stocks/search_direct
 * $config['replacer'] = array('search_direct' => 'edit');
 * Breadcrumb = Home > Warehouse > Stocks > Edit
 *
 * 2. Hide link
 *
 * Example:
 * =======
 * URL = http://localhost/arstock/warehouse/stocks/search_direct
 * $config['replacer'] = array('search_direct' => 'edit', 'warehouse' => '');
 * Breadcrumb = Home > Stocks > Edit
 *
 * 3. Change URL (since version 5.10.1)
 *
 * Example:
 * =======
 * URL = http://localhost/arstock/warehouse/stocks/search_direct
 * $config['replacer'] = array('search_direct' => 'edit', 'warehouse' => array('/dept_warehouse|warehouse department'));
 * Breadcrumb = Home > Warehouse Department > Stocks > Edit
 * Warehouse Department's url = http://localhost/arstock/dept_warehouse/
 *
 * 4. Add new crumbs (since version 5.10.1)
 *
 * Example:
 * =======
 * URL = http://localhost/arstock/warehouse/stocks/search_direct
 * $config['replacer'] = array('search_direct' => 'edit', 'warehouse' => array('/dept_list|departments', 'warehouse department'), 'stocks' => array('stocks', '/warehouse/action_list|actions'), 'edit' => array('edit', 'item 1', 'item_2|item 2'));
 * Breadcrumb = Home > Departments > Warehouse Department > Stocks > Actions > Edit > Item > Item 2
 * Departments' url 			= http://localhost/arstock/dept_list/
 * Warehouse Department's url 	= http://localhost/arstock/warehouse/
 * Stocks' url 					= http://localhost/arstock/warehouse/stocks
 * Actions' url 				= http://localhost/arstock/warehouse/action_list
 * Edit's url 					= http://localhost/arstock/warehouse/stocks/edit
 * Item's url 					= http://localhost/arstock/warehouse/stocks/edit
 * Item 2's url 				= http://localhost/arstock/warehouse/stocks/edit/item_2
 */
$config['replacer'] = array();

/**
 * --------------------------
 * Config: Exclude
 * --------------------------
 * Default value:
 * $config['exclude'] = array('');
 *
 * Can hide links that written in array
 *
 * Example:
 * =======
 * If we set $config['exclude'] = array('stocks', 'warehouse') then from this URL "http://localhost/arstock/warehouse/stocks/insert"
 * we get breadcrumb: Home > Insert
 */
$config['exclude'] = array('');

/**
 * ------------------------------------
 * Config: Exclude Segment
 * ------------------------------------
 * Default value:
 * $config['exclude_segment'] = array();
 *
 * Can hide segments
 *
 * Example:
 * =======
 * Look at this example URL:
 * http://mysite.com/en/search/results
 * http://mysite.com/fr/search/results
 * If we set $config['exclude'] = array(1) then everything in segment 1 which are 'en' & 'fr' will be hide. We get breadcrumb:
 * Home > Search > Results
 */
$config['exclude_segment'] = array();

/**
 * --------------------------
 * Config: Wrapper
 * --------------------------
 * Default value:
 * $config['use_wrapper'] = FALSE;
 * $config['wrapper'] = '<ul>|</ul>';
 * $config['wrapper_inline'] = '<li>|</li>';
 *
 * We set this if we want to make breadcrumb have it's own style.
 * it possible to return the breadcrumb in a list (<ul><li></li></ul>) or something else as configure below.
 * Set use_wrapper to TRUE to use this feature.
 */
$config['use_wrapper'] = TRUE;
$config['wrapper'] = '<ul id="breadcrumbs">|</ul>';
$config['wrapper_inline'] = '<li class="crumb">|</li>';

/**
 * ---------------------
 * Config: Unlink
 * ---------------------
 * Default value:
 * $config['unlink_last_segment'] = FALSE;
 *
 * If set to TRUE then the last segment in breadcrumb will not have a link.
 */
$config['unlink_last_segment'] = TRUE;

/**
 * ---------------------
 * Config: Hide number
 * ---------------------
 * Default value:
 * $config['hide_number'] = TRUE;
 *
 * If set to TRUE then any number without a word in a segment will be hide.
 *
 * Example:
 * =======
 * http://mysite.com/blog/2009/08/7-habbits/
 * will have breadcrumbs: Home > Blog > 7 Habbits
 * Otherwise if set to FALSE it will produce: Home > Blog > 2009 > 08 > 7 Habbits
 * Notes: If the last segment is a number then it always shown whether this config
 * set to TRUE or FALSE
 */
$config['hide_number'] = TRUE;

/**
 * -------------------------
 * Config: Strip characters
 * -------------------------
 * Default value:
 * $config['strip_characters'] =  array ('_', '-', '.html', '.php', '.htm');
 *
 * All characters in the array will be stripped from breadcrumbs
 *
 * Example:
 * =======
 * http://mysite.com/blog/7-habbits/request.html
 * will have breadcrumbs: Home > Blog > 7 Habbits > Request
 */
$config['strip_characters'] = array ('_', '-', '.html', '.php', '.htm');

/**
 * ------------------------------------
 * Config: Strip by Regular Expression
 * ------------------------------------
 * Default value:
 * $config['strip_regexp'] =  array ();
 *
 * All regular expression in the array will be stripped from breadcrumbs
 *
 * Example:
 * =======
 * http://mysite.com/blog/7-habbits/request-300.html
 * set config to: $config['strip_regexp'] =  array ('/-[0-9]+.html/');
 * then we will have breadcrumbs: Home > Blog > 7 Habbits > Request
 */
$config['strip_regexp'] = array ();

/* End of file breadcrumb.php */
/* Location: ./system/application/config/breadcrumb.php */
