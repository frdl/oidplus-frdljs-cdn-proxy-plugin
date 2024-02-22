<?php
namespace Frdlweb\OIDplus\Js;

use Frdlweb\OIDplus\OIDplusCDNProxyPagePlugin;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusException; 

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_Frdlweb\OIDplus\OIDplusCDNProxyPagePlugin', false)) {
	//throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
	return;
}

//originHeaders();
header('Content-Type:application/javascript');
#
//var __token 
//= window.csrf_token 
//= csrf_token;
//alert(__token) ;
//var jquery = window.$;
$jscode = <<<JSCODE
function io4test(){
 alert('test');	
}
JSCODE;

echo $jscode;
echo frdlwebJS();


function frdlwebJS(){
 $FRDLWEB_JS_CONFIG_QUERY =	OIDplus::baseConfig()->getValue('FRDLWEB_DEFAULT_JS_CONFIG_QUERY', OIDplusCDNProxyPagePlugin::DEFAULT_JS_CONFIG_QUERY );
	
 if(false === $FRDLWEB_JS_CONFIG_QUERY || 'false' == $FRDLWEB_JS_CONFIG_QUERY){
	 return '';
 }
	
$jscode = <<<JSCODE
 ((q, w,d)=>{
$(document).ready(()=>{
var s=d.createElement('script');
s.setAttribute('src', 'https://cdn.frdl.de/webfan.js?' + q);		
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
});	 

})('$FRDLWEB_JS_CONFIG_QUERY', window, document);
JSCODE;
 	
 return $jscode;
}

