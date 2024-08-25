<?php
/**
 * Plugin Name: IO4 
 * Description: This is an example OIDplus/IO4-Plugin aware and enabled Plugin.
 * Version: 0.0.1
 * Author: Frdlweb
 * Author URI: https://frdl.de
 * License: MIT
 */
namespace Frdlweb\OIDplus\CDN\plugin;

	use ViaThinkSoft\OIDplus\Core\OIDplus;
	use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
	use ViaThinkSoft\OIDplus\Core\OIDplusException;
	use ViaThinkSoft\OIDplus\Core\OIDplusObject;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;
	use ViaThinkSoft\OIDplus\Core\OIDplusPlugin;
 
    use Frdlweb\OIDplus\Plugins\AdminPages\IO4\OIDplusPagePublicIO4;
    use Frdlweb\OIDplus\Plugins\PublicPages\CDNProxy\OIDplusCDNProxyPagePlugin;

function io4_plugin_public_pages_tree(?string $ra_mail = null){
	 
	
		if (file_exists(__DIR__ . '/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__) . 'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}
	
	$json =OIDplusCDNProxyPagePlugin::$oidplus_public_pages_tree_json;
		$Array = (new \Wehowski\Helpers\ArrayHelper($json)) ;
		$Array->after(0)->add([
		    'id' => 'oidplus:home',
		 	'icon' => $tree_icon,
			 'a_attr'=>[
			 	 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			 ],
			 //  //'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			'text' => _L('Home'),
	   ]);
	$json = $Array->all();
	OIDplusCDNProxyPagePlugin::$oidplus_public_pages_tree_json = $json;
//	die(print_r($oidplus_public_pages_tree_json,true));
}


add_action(
		'oidplus_public_pages_tree',
		__NAMESPACE__.'\io4_plugin_public_pages_tree',
		0//,
		//string $include_path = null,
	);

//you can use autowiring as from container->invoker !!!
return (function($container){
	//print_r(get_class($container));
});
