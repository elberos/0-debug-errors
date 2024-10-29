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
add_filter('site_transient_update_plugins', function ($value)
{
	$name = plugin_basename(__FILE__);
	if (isset($value->response[$name]))
	{
		unset($value->response[$name]);
	}
	return $value;
});


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


/**
 * Show stack trace
 */
function elberos_show_stack_trace($e)
{
	$trace = $e->getTrace();
	echo "<br/>\n";
	foreach ($trace as $index => $item)
	{
		if (isset($item['file']))
		{
			echo $index . ") " . htmlspecialchars($item['file']) .
				"(" . htmlspecialchars($item['line']) . ")";
			echo ": " . htmlspecialchars($item['function']);
		}
		else if (isset($item['class']))
		{
			echo $index . ") " . htmlspecialchars($item['class']);
			echo ": " . htmlspecialchars($item['function']);
		}
		else
		{
			echo "internal: " . htmlspecialchars($item['function']);
		}
		
		echo "<br/>\n";
	}
}


/**
 * Show error
 */
function elberos_show_error($e)
{
	if (!$e) return;
	
	status_header(500);
	http_response_code(500);
	
	echo "<b>Fatal Error</b><br/>";
	if ($e instanceof \Exception)
	{
		$e = $e->getPrevious() ? $e->getPrevious() : $e;
		
		echo nl2br(htmlspecialchars($e->getMessage())) . "<br/>\n";
		echo "in file " . htmlspecialchars($e->getFile()) . ":" .
			htmlspecialchars($e->getLine()) . "\n";
		
		/* Show stack trace */
		elberos_show_stack_trace($e);
	}
	else
	{
		echo nl2br(htmlspecialchars($e['message'])) . "<br/>\n";
		if (isset($e["file"]))
		{
			echo "in file " . htmlspecialchars($e["file"]) . ":" .
				(isset($e["line"]) ? htmlspecialchars($e["line"]) : 0) . "\n";
		}
	}
}

/* Обработчик ошибок */
set_exception_handler( function ($e){
	if (!$e) return;
	elberos_show_error($e);
	exit();
} );

/* Shutdown функция */
register_shutdown_function( function(){
	
	$e = error_get_last();
	
	if (!$e) return;
	if (!($e['type'] & (E_COMPILE_ERROR))) return;
	
	elberos_show_error($e);
} );
