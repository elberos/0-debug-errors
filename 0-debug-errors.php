<?php
/**
 * Plugin Name: Стандартный обработчик ошибок PHP
 * Description: Включает обычный вывод ошибок PHP для WordPress
 * Version:     0.1.0
 * Author:      Elberos team <support@elberos.org>
 * License:     Apache License 2.0
 *
 *  (c) Copyright 2019-2021 "Ildar Bikmamatov" <support@elberos.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

defined( 'ABSPATH' ) || exit;


/* Remove plugin updates */
add_filter( 'site_transient_update_plugins', 'debug_errors_filter_plugin_updates' );
function debug_errors_filter_plugin_updates($value)
{
	$name = plugin_basename(__FILE__);
	if (isset($value->response[$name]))
	{
		unset($value->response[$name]);
	}
	return $value;
}

/* Выключаем html отладчик */
add_filter('qm/dispatchers', function($dispatchers, $qm){
	
	unset($dispatchers['html']);
	//var_dump($dispatchers);
	
	return $dispatchers;
}, 999999, 2);


/* Включаем стандартный обработчик ошибок */
if (!defined('WP_DISABLE_FATAL_ERROR_HANDLER'))
{
	define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
}

/* Обработчик ошибок */
set_exception_handler( function ($e){
	if ($e)
	{
		status_header(500);
		echo "<b>Fatal Error</b>: ";
		echo nl2br($e->getMessage()) . " ";
		echo "in file " . $e->getFile() . ":" . $e->getLine();
	}	
} );

/* Shutdown функция */
register_shutdown_function( function(){
	
	$e = error_get_last();
	
	if (
		empty( $e ) ||
		!( $e['type'] & (E_ERROR | E_CORE_ERROR | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR) )
	)
	{
		return;
	}
	
	echo "<b>Fatal Error</b><br/>";
	echo nl2br($e['message']) . "\n";
} );

add_filter( 'pre_update_option_active_plugins', 'debug_errors_pre_update_option_active_plugins', 999999);
function debug_errors_pre_update_option_active_plugins($plugins)
{
	
	if ( empty( $plugins ) ) {
		return $plugins;
	}
	
	$name = plugin_basename(__FILE__);
	if (($key = array_search($name, $plugins)) !== false)
	{
		unset($plugins[$key]);
	}
	array_unshift($plugins, $name);
	
	return $plugins;
	
}

add_action
(
	'plugins_loaded',
	function ()
	{
		if (!defined('QM_ERROR_FATALS'))
		{
			define( 'QM_ERROR_FATALS', E_ERROR | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );
		}
	}
);
