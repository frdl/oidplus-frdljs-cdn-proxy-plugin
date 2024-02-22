<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Frdlweb\OIDplus;

use ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusConfig;
use ViaThinkSoft\OIDplus\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException;
use ViaThinkSoft\OIDplus\OIDplusOID;
use ViaThinkSoft\OIDplus\OIDplusRA;
use ViaThinkSoft\OIDplus\OIDplusNaturalSortedQueryResult;


\defined('INSIDE_OIDPLUS') or die;



class OIDplusCDNProxyPagePlugin  extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2 /* modifyContent */
{

	const DEFAULT_CDN_BASEPATH = 'cdn';	
	const DEFAULT_JS_CONFIG_QUERY = 'library.jquery.overwrite=false&DEBUG.enabled=true&website.consent.ads=false&website.consent.enabled=false&angularjs.html5mode.requireBase=false&angularjs.html5mode.rewriteLinks=false&angularjs.html5mode.enabled=false&website.worker.enabled=false&website.darkmode.enabled=false&website.scroll.enabled=false';		
	
	public function init($html = true) {
		OIDplus::config()->prepareConfigKey('FRDLWEB_DEFAULT_JS_CONFIG_QUERY', 'Set the config query for Frdlweb.js. <strong>Set to false to disable!</strong> <a href="https://frdl.de/blog/view/1208/webfanjs-frdlwebjs-configuration-parameters" target="_blank">Help on config query</a>', 'library.jquery.overwrite=false&DEBUG.enabled=true&website.consent.ads=false&website.consent.enabled=false&angularjs.html5mode.requireBase=false&angularjs.html5mode.rewriteLinks=false&angularjs.html5mode.enabled=false&website.worker.enabled=false&website.darkmode.enabled=false&website.scroll.enabled=false', OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
			//if (($value != '') && !oid_valid_dotnotation($value, false, false, 1)) {
		//		throw new OIDplusException("Please enter a valid OID in dot notation or nothing");
		//	}
		});


	}
	 
	
	//public function handle404(string $request): bool {
	//	die($request);
//		return false;
//	}

	

	public function modifyContent($id, &$title, &$icon, &$text) {
		 

	}

 

 
 	//public function action(string $actionID, array $params): array {
   //       return parent::action($actionID, $params);
 	//}

 



 //	public function gui($id, &$out, &$handled) {
 //		
  //  }


	public function httpHeaderCheck(&$http_headers) {
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://ads.google.com/"; // Beispiel
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://cdn.frdl.de/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://cdn.startdir.de/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://webfan.de/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://registry.frdl.de/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://pagead2.googlesyndication.com/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://googleads.g.doubleclick.net/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://adservice.google.de/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://io4.xyz.webfan3.de/";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "*";


		$http_headers["Content-Security-Policy"]["script-src"][] = "https://ads.google.com/"; // Beispiel
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://cdn.frdl.de/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://cdn.startdir.de/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://webfan.de/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://registry.frdl.de/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://pagead2.googlesyndication.com/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://googleads.g.doubleclick.net/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://adservice.google.de/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://io4.xyz.webfan3.de/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "*";

		$http_headers["Content-Security-Policy"]["style-src"][] = "https://ads.google.com/"; // Beispiel
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://cdn.frdl.de/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://cdn.startdir.de/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://webfan.de/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://registry.frdl.de/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://pagead2.googlesyndication.com/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://googleads.g.doubleclick.net/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://adservice.google.de/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://io4.xyz.webfan3.de/";
		$http_headers["Content-Security-Policy"]["style-src"][] = "*";


		$http_headers["Content-Security-Policy"]["default-src"][] = "https://ads.google.com/"; // Beispiel
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://cdn.frdl.de/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://cdn.startdir.de/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://webfan.de/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://registry.frdl.de/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://pagead2.googlesyndication.com/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://googleads.g.doubleclick.net/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://adservice.google.de/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "https://io4.xyz.webfan3.de/";
		$http_headers["Content-Security-Policy"]["default-src"][] = "*";


		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://ads.google.com/"; // Beispiel
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://cdn.frdl.de/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://cdn.startdir.de/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://webfan.de/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://registry.frdl.de/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://pagead2.googlesyndication.com/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://googleads.g.doubleclick.net/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://adservice.google.de/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://io4.xyz.webfan3.de/";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "*";


	}




	 
}
