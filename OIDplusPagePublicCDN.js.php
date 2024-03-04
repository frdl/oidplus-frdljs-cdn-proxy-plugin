<?php
namespace Frdlweb\OIDplus\Js;

use Frdlweb\OIDplus\OIDplusCDNProxyPagePlugin;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusException; 

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

OIDplus::init(true);

error_reporting(OIDplus::baseConfig()->getValue('DEBUG') ? E_ALL : 0);
@ini_set('display_errors', OIDplus::baseConfig()->getValue('DEBUG') ? '1' : '0');

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));
ob_start(); 
//  DONE ALREADY ! OIDplus::init(true);
// session_write_close();
OIDplus::invoke_shutdown();
 originHeaders();
header('Content-Type:application/javascript');
//header('Connection: close');
if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_Frdlweb\OIDplus\OIDplusCDNProxyPagePlugin', false)) {
	//throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
	return;
}
 


//var __token 
//= window.csrf_token 
//= csrf_token;
//alert(__token) ;
//var jquery = window.$;

echo frdlwebJS();


function frdlwebJS(){
 $FRDLWEB_JS_CONFIG_QUERY =	OIDplus::baseConfig()->getValue('FRDLWEB_DEFAULT_JS_CONFIG_QUERY', OIDplusCDNProxyPagePlugin::DEFAULT_JS_CONFIG_QUERY );
	
 if(false === $FRDLWEB_JS_CONFIG_QUERY || 'false' == $FRDLWEB_JS_CONFIG_QUERY){
	 return '';
 }
//s.setAttribute('src', 'https://io4.xyz.webfan3.de/webfan.js?cdn=https://cdn.startdir.de&?' + q);
	
	$cdn = OIDplus::baseConfig()->getValue('FRDLWEB_CDN_PROXY_TARGET_BASE', OIDplusCDNProxyPagePlugin::DEFAULT_CDN_MASTER_BASEURI );
	
	    $CDN_BASEPATH =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_RELATIVE_URI', OIDplusCDNProxyPagePlugin::DEFAULT_CDN_BASEPATH );
	    $BASE_URI = rtrim(OIDplus::webpath(OIDplus::localpath(),OIDplus::PATH_ABSOLUTE_CANONICAL), '/ ').'/'.trim($CDN_BASEPATH, '/ ')
			//.'/'
			;	
	
	
$jscode = <<<JSCODE
((q, w,d)=>{
//$(document).ready(()=>{
var s=d.createElement('script');
s.setAttribute('src', '$cdn/webfan.js?cdn=$BASE_URI&?' + q);		
s.async='defer';
s.onload=()=>{
  window.frdlweb.ready(()=>{		
	 if('undefined'!==typeof $.xhrPool ){
	   return;
	 }
	  $.xhrPool = []; 
	  $.xhrPool.abortAll = function() {    
		  _.each(this, function(jqXHR) {      
			  jqXHR.abort();   
		  });   
	  };
   
	  $.ajaxSetup({     
		  beforeSend: function(jqXHR) {       
			  $.xhrPool.push(jqXHR);  
		  }
	  });
	  
  });
};
 d.head.appendChild(s);		
//});	 

})('$FRDLWEB_JS_CONFIG_QUERY', window, document);
JSCODE;
 	
 return $jscode;
}

