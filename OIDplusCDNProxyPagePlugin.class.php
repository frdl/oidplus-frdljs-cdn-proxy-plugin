<?php


namespace Frdlweb\OIDplus;

 

use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusPagePublicAttachments;
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

	const DEFAULT_CDN_BASEPATH = 'assets-cdn';
	const DEFAULT_CDN_MASTER_BASEURI = 'https://cdn.startdir.de';	
	const DEFAULT_JS_CONFIG_QUERY = 'library.jquery.overwrite=false&DEBUG.enabled=true&website.consent.ads=false&website.consent.enabled=false&angularjs.html5mode.requireBase=false&angularjs.html5mode.rewriteLinks=false&angularjs.html5mode.enabled=false&website.worker.enabled=false&website.darkmode.enabled=false&website.scroll.enabled=false';		
	
	protected $cdnCacheDir;
	protected $cdnCacheExpires;
	
	public function __construct(){	 		
		$this->cdnCacheDir = OIDplus::baseConfig()->getValue('CDN_CACHE_DIRECTORY', OIDplus::localpath().'userdata/cache/cdn-assets/' );
		$this->cdnCacheExpires = OIDplus::baseConfig()->getValue('CDN_CACHE_EXPIRES', 60 * 15 );	
	}
	
	
	public function init($html = true) {
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_CDN_RELATIVE_URI', 'The CDN base uri to the CDN/Proxy -Module. Example: "cdn/" or "assets/"', self::DEFAULT_CDN_BASEPATH, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
		  
		});		
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_DEFAULT_JS_CONFIG_QUERY', 'Set the config query for Frdlweb.js. Set to false to disable!  Help: https://frdl.de/blog/view/1208/webfanjs-frdlwebjs-configuration-parameters', 'library.jquery.overwrite=false&DEBUG.enabled=true&website.consent.ads=false&website.consent.enabled=false&angularjs.html5mode.requireBase=false&angularjs.html5mode.rewriteLinks=false&angularjs.html5mode.enabled=false&website.worker.enabled=false&website.darkmode.enabled=false&website.scroll.enabled=false', OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
			//if (($value != '') && !oid_valid_dotnotation($value, false, false, 1)) {
		//		throw new OIDplusException("Please enter a valid OID in dot notation or nothing");
		//	}
		});

		OIDplus::config()->prepareConfigKey('FRDLWEB_CDN_PROXY_TARGET_BASE', 'The CDN base uri to get/proxy from/to. Can be e.g.: "https://cdn.startdir.de" or "https://cdn.frdl.de" or "https://cdn.webfan.de"', self::DEFAULT_CDN_MASTER_BASEURI, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
			 
			if(!in_array($value, ['https://cdn.startdir.de', 'https://cdn.frdl.de', 'https://cdn.webfan.de', ])){
				throw new OIDplusException('Can be e.g.: "https://cdn.startdir.de" or "https://cdn.frdl.de" or "https://cdn.webfan.de"');
			}
		});
	}
	 
	
	public function publicSitemap(&$out) { 
		//$out[] = OIDplus::getSystemUrl().'?goto='.urlencode('com.frdlweb.freeweid'); 
	}

	public function tree(array &$json, ?string $ra_email = null, bool $nonjs = false, string $req_goto = ''): bool { 

		if (file_exists(__DIR__ . '/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__) . 'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}
/*
		$json[] = array(
			// Marschall 13.04.2023 wieder umgeändert
			'id' => 'com.frdlweb.freeweid',
			// 'id' => 'oidplus:com.viathinksoft.freeoid',
			'icon' => $tree_icon,
			'text' => str_replace('OID', 'WEID as OID Arc', _L('Register a free OID')),
		);
*/
		return false;
	}


	public function tree_search($request) {
		$ary = array();

		if ($obj = OIDplusObject::parse($request)) {
			if ($obj->userHasReadRights()) {
				/*
                do {
					$ary[] = $obj->nodeId();
				} while ($obj = $obj->getParent());
				*/

				/**/
				while ($obj = $obj->getParent()) {
					$ary[] = $obj->nodeId();
				}

				$ary = array_reverse($ary);
			}
		}
		return $ary;
	}

	protected function cdn_write_cache(string $out, string $cacheFile){
		if(!is_dir(dirname($cacheFile))){
		 mkdir(dirname($cacheFile), 0755, true);	
		}
		file_put_contents($cacheFile, $out);
		chmod($cacheFile, 0655);
	}

	/**
	 * @param string $cacheFile
	 * @param int $rdapCacheExpires
	 * @return array|null
	 */
	protected function cache_read_serve(string $cacheFile, int $rdapCacheExpires){
		if (file_exists($cacheFile) && filemtime($cacheFile) >= time() - $rdapCacheExpires) {
	        //    OIDplus::invoke_shutdown();
            	\VtsBrowserDownload::output_file($cacheFile, '', 1);
			die();
		}
		return false;	
	}
	
   public function handle404(string $request): bool {
	 	//die($request);
	    $CDN_BASEPATH =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_RELATIVE_URI', self::DEFAULT_CDN_BASEPATH );
	    $BASE_URI = rtrim(OIDplus::webpath(OIDplus::localpath(),OIDplus::PATH_ABSOLUTE_CANONICAL), '/ ').'/'.trim($CDN_BASEPATH, '/ ').'/';
	    if(!str_starts_with($request, $CDN_BASEPATH))return false;
	   ob_start(); 
	   
   if (!isset($_COOKIE['csrf_token'])) {
	// This is the main CSRF token used for AJAX.
	$token = OIDplus::authUtils()->genCSRFToken();
	OIDplus::cookieUtils()->setcookie('csrf_token', $token, 0, false);
	unset($token);
  }

  if (!isset($_COOKIE['csrf_token_weak'])) {
	// This CSRF token is created with SameSite=Lax and must be used
	// for OAuth 2.0 redirects or similar purposes.
	$token = OIDplus::authUtils()->genCSRFToken();
	OIDplus::cookieUtils()->setcookie('csrf_token_weak', $token, 0, false, 'Lax');
	unset($token);
  }	   
	   
	   
	   OIDplus::invoke_shutdown();
	   
	    //$isCdnUri = preg_match('@^/'.preg_quote($request,'@').'/(.+)$@', $_SERVER['REQUEST_URI'], $m);
	    $isCdnUri = str_starts_with($request, $CDN_BASEPATH) 
			&& (
			preg_match('/'.preg_quote($CDN_BASEPATH,'/@').'(?P<oid>([0-9\.^\/]+))?(?P<uri>(.+))/', str_replace('oid:', '', $_SERVER['REQUEST_URI']), $matches)
		//	|| preg_match('/'.preg_quote($CDN_BASEPATH,'/@').'(?P<id>([^\/]+))\/(?P<uri>(.+))/', $_SERVER['REQUEST_URI'], $matches)
		 );
	   
	       $uri = ltrim($matches['uri'], '/ ');	  
	       $p = explode('/', $uri);	  	   
           $id = $p[0];
	      if(str_starts_with($id, 'weid:')){
	          $oid =  'oid:'.\Frdl\Weid\WeidOidConverter::weid2oid($id);
		   }elseif(!empty($matches['oid'])){
			  $oid =  'oid:'.trim($matches['oid'], '/ ');
		  }else{
			  $oid =false;
		  }            
		   
		   $objGoto = OIDplusObject::findFitting($oid); 
       
	   
	  		   
	        $iris = array();
		   	$oids = array();
		    
		  if($isCdnUri && !$objGoto){ 
			$last = false;
		    $q = "select * from ###iri where ";
		    $wheres = [];
		    $sqlParams = [];
		    foreach($p as $dir){
				$d = trim($dir, '/ ');
				$sqlParams[] = $d;
				$wheres[] = "LOWER(`name`) = LOWER(?) ";
			}
		    $q.= implode(" or ", $wheres);
		    $q.= " order by oid asc";
			$res2 = OIDplus::db()->query($q, $sqlParams);
			$o = false;
			$l = false;
			$u = '';
			while ($row2 = $res2->fetch_array()) {
				 
				$o = OIDplusObject::findFitting($row2['oid']);
				if($o){
					$u.=$row2['name'].'/';
				}
				//if(!$last || str_starts_with($row2['oid'], $last) ){
				  $iris[] = $row2['name'];
				  $oids[] = $row2['oid'];
				  $last = $row2['oid'];
				//}elseif(!str_starts_with($row2['oid'], $last)){
					//die('invalid iri');
				//}
			} 
		      $l = OIDplusObject::findFitting($last);
			  $objGotoLastFromUri =  count($oids)>0 && $last === $oids[count($oids)-1]
				  ? OIDplusObject::findFitting($oids[count($oids)-1])
				  : false; 
			  
			  if($objGotoLastFromUri && $l && $o
				&&  strtolower($objGotoLastFromUri->getIriNotation(false)) === strtolower($l->getIriNotation(false))
				&&  strtolower($o->getIriNotation(false)) === strtolower($l->getIriNotation(false)) 
				&&  '/'.strtolower($uri) === strtolower($objGotoLastFromUri->getIriNotation(false).'/'.$p[count($p)-1]) 
				 ){ 
				  /*  die( strtolower($objGotoLastFromUri->getIriNotation(false).'/'.$p[count($p)-1]) .'<br />$objGotoLastFromUri: '.($objGotoLastFromUri ? strtolower($objGotoLastFromUri->getIriNotation(false)) : $objGotoLastFromUri).'<br />$p: '.implode('/',$p).'<br />$uri: '.strtolower($uri) .'<br />$u: '.$u.'<br />$o: '.$o->getIriNotation(false).'<br />$l: '.($l ? strtolower($l->getIriNotation(false)) : $l));
				  */
				  $objGoto = OIDplusObject::findFitting($objGotoLastFromUri->nodeId(true)); 
			  }
			  
			
			  
			  
			  
		   }//!$objGoto -> iris	   
	 
		   if(!$objGoto){
		 	  $objGoto = OIDplusObject::findFitting($id); 
		   }	   
		    $oid = $objGoto ? $objGoto->nodeId(false) : $oid;
	        $oidWithNs = 'oid:'.$oid;
		    $id = $objGoto ? $objGoto->nodeId(true) : false;
		   
		    $file =count($p) > 0 ? $p[count($p)-1] : false;
	   
 		   if(!$objGoto){
			  $objGoto = OIDplusObject::findFitting($uri); 
		   }		   
	   

	   
	   
	   if ($isCdnUri || is_object($objGoto)) {
		   //set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));


		   //die(OIDplusObject::exists($id).' - '.OIDplusObject::exists('oid:'.$oid));


		    
		    if($id
			    && is_object($objGoto)
			     && ( OIDplusObject::exists($id) 
				//	 && OIDplusObject::exists('oid:'.$oid)
					)
			  //  && !empty($file)
			    && class_exists(OIDplusPagePublicAttachments::class)
				&& !OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplusPagePublicAttachments', false) 
			   
			   ){	
				$uploaddir = OIDplusPagePublicAttachments::getUploadDir($id);
				 
				if((false===$file || empty($file) || ''===$file) || '/' === substr($uri, strlen($uri)-1,1) ){
				  $files = glob($uploaddir 
								. DIRECTORY_SEPARATOR
								//  .'/'
								 . '*');
			 
					  $linkDirectory = $BASE_URI.$objGoto->getIriNotation(false).'/';
					  $permaLinkDirectory = $BASE_URI.$id.'/';
					
					  $links = [];
						foreach($files as $f){					 
							$links[$BASE_URI
						             .trim(strtolower($objGoto->getIriNotation(false)), '/ ')
								 //  .$objGoto->getIriNotation(false)
								.'/'.basename($f) ] = 
								$BASE_URI
						      //.strtolower($objGoto->getIriNotation(false))
								  .trim($objGoto->getIriNotation(false), '/ ')
								.'/'.basename($f);					
						}		
					
					$html = '';
					$html.='<h1>Files of '.$objGoto->getTitle().'</h1>';
					
					if($objGoto->userHasReadRights() ){
					
					$html.='<ul>'; 
					 foreach($links as $normalized => $link){
						 $html.=sprintf('<li><a href="%s" target="_blank">%s</a> : <a href="%s" target="_blank">%s</a>'
										.' <a href="%s" target="_blank">%s</a></li>',
										$link, basename($link),
										$normalized, 'normalized/pretty-url', 
										$permaLinkDirectory.basename($link), 'OID-/Permalink');
					 }
					$html.='</ul>';
					}else{
						$html.=' - You have no access to read the file list, please login!';
					}
					
					
					$pageHTML = $this
						// OIDplus::gui() 
						->showMainPage($objGoto->getTitle(),
																 'List files of '.$id, 
																  '',//string $static_icon,
																 $html,
																 [
																 '<base href="'
															 
								/*	.OIDplus::canonicalURL()						 */		 
															 .(OIDplus::isSSL()
															   ? 'https://'
															   : 'http://')
															  .$_SERVER['SERVER_NAME'].OIDplus::webpath(null, OIDplus::PATH_RELATIVE).'/'
															 .'">' , 
																										
																 ], 
																 $id);
					echo $pageHTML;
					// return true;
					die();
				}
				
				
					$filename =$file;
	

					
			
				$local_file = $uploaddir.'/'.$filename;

           	   if ($id && is_object($objGoto) && ( OIDplusObject::exists($id) 
												//  && OIDplusObject::exists($oidWithNs)
												 ) && !file_exists($local_file)) {
		             http_response_code(404);
 
				   $pageHTML = // OIDplus::gui() 
					   $this
					 //  ->showSimplePage(
					   ->showMainPage(
					   _L('Not found'), 
												   _L(sprintf('The file "%s" does not exist in this directory!'.$oidWithNs,$filename)),
												   '',
												   (new OIDplusException(_L(sprintf('The file %s does not exist',$filename))))->getMessage(),
												    [
																 '<base href="'
																	 .htmlentities('https://'.$_SERVER['SERVER_NAME'].rtrim(OIDplus::webpath(OIDplus::localpath(),
																										  OIDplus::PATH_RELATIVE), 
																						 '/ ').'/')
																	 .'" />', 
																 ],
					   $id												 
				   );
				   				
				   echo $pageHTML;
					// return true;
					die();
	            }elseif($id && is_object($objGoto) && ( OIDplusObject::exists($id)
													 //  && OIDplusObject::exists('oid:'.$oid)
													  ) && file_exists($local_file)){//404 from local OID attachments dir	 

				   
					if (strpos($filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));	
			    	if (strpos($filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));	
				    if (strpos($filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));	
				    if (strpos($filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));			   
				  
				   if(!$objGoto->userHasReadRights() ){
					    throw new OIDplusException(_L(sprintf('You have no access to %s, please login!',$filename)));
				   }				   
				   
				//   OIDplus::invoke_shutdown();            
				   \VtsBrowserDownload::output_file($local_file, '', 1);				
				   return true;
			   }
			}//$objGoto
		   
		//   session_write_close();
		    originHeaders();
		    $file = $this->cdnCacheDir . explode('?', $uri)[0];
		    $filename = basename($file);
		   
		   	$test=explode('.', $filename);  

		   if('php' === $test[count($test)-1]){	
			   throw new OIDplusException(_L(sprintf('The file %s is not wanted!',$filename)));
		   }
		   
		    if(!$this->cache_read_serve($file, $this->cdnCacheExpires)){
				$CDN_TARGET_BASE =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_PROXY_TARGET_BASE', self::DEFAULT_CDN_MASTER_BASEURI );
				$url = rtrim($CDN_TARGET_BASE, '/ ').'/'.$uri;
				//die($target);
				$opts =[     
					'http'=>[          
						'method'=>$_SERVER['REQUEST_METHOD'],           
						//'header'=>"Accept-Encoding: deflate, gzip\r\n",           
					],	
				];
  
				$context = stream_context_create($opts);

				// Open the file using the HTTP headers set above   
				$result = @file_get_contents($url, false, $context);
				if(false === $result){      
					http_response_code(404); 
				   $pageHTML = // OIDplus::gui() 
					   $this
					 //  ->showSimplePage(
					   ->showMainPage(
					   _L('Not found'), 
												   _L(sprintf('The file "%s" does not exist at "%s"!',$filename, $url)),
												   '',
												   (new OIDplusException(_L(sprintf('The file %s does not exist',$filename))))->getMessage(),
												    [
																 '<base href="'
																	 .htmlentities(
																		 'https://'.$_SERVER['SERVER_NAME']
																		 .rtrim(OIDplus::webpath(OIDplus::localpath(),
																										  OIDplus::PATH_RELATIVE), 
																						 '/ ').'/')
																	 .'" />', 
																 ]
												  );
				   				
				   echo $pageHTML;
					// return true;
					die();
				}//404 from cdn
	
       
			//	foreach($http_response_header as $i => $header){
					//header($header);      
			//	}
	
				//file_put_contents($file, $result);
				 $this->cdn_write_cache($result, $file);
				
				 if(!$this->cache_read_serve($file, $this->cdnCacheExpires)){
					 throw new OIDplusException(_L(sprintf('The file %s does not exist',$filename)));
				 }else{
					 return true; 
				 }
			}
		   /*  
		   die($oid.'<br />'.$isCdnUri.'<br />'.$_SERVER['REQUEST_URI'].'<br />'.$request.'<br />'.$CDN_BASEPATH
			   .'<br />'.print_r($matches,true)
			    .'<br />'.print_r($p,true)
			    .'<br />'.print_r($objGoto ? $objGoto->getIris(): false,true)
			    .'<br />'.implode('/', $iris)
			    .'<br />'.print_r($oids,true)
			    .'<br />'.$last
			    .'<br /><br />'.$file
			    .'<br />'.$id
			   .'<br />'.$uri 
			  );
			
		   
		   $url = OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE_CANONICAL).'download.php?id='.urlencode($id).'&filename='.urlencode(basename($file));*/
		   
	   }
	   
	   
    return false;
   }

	

	public function modifyContent($id, &$title, &$icon, &$text) {		
			  
		$CDN_BASEPATH =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_RELATIVE_URI', self::DEFAULT_CDN_BASEPATH );
	    $BASE_URI = rtrim(OIDplus::webpath(OIDplus::localpath(),OIDplus::PATH_ABSOLUTE_CANONICAL), '/ ').'/'.trim($CDN_BASEPATH, '/ ').'/';
		
		    $payload = '<br /> <a href="'.$BASE_URI.$id.'/" class="gray_footer_font">'._L('CDN/Files').'</a>';

		$text = str_replace('<!-- MARKER 6 -->', '<!-- MARKER 6 -->'.$payload, $text);	 

	}

 

 
 	 public function action(string $actionID, array $params): array {
          return parent::action($actionID, $params);
 	 }

 



    public function gui($id, &$out, &$handled) {
 	
    }


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


	
	private function getCommonHeadElems(string $title): array {
		// Get theme color (color of title bar)
		$design_plugin = OIDplus::getActiveDesignPlugin();
		$theme_color = is_null($design_plugin) ? '' : $design_plugin->getThemeColor();

		$head_elems = array();
		$head_elems[] = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
		$head_elems[] = '<meta charset="UTF-8">';
		if (OIDplus::baseConfig()->getValue('DATABASE_PLUGIN','') !== '') {
			$head_elems[] = '<meta name="OIDplus-SystemTitle" content="'.htmlentities(OIDplus::config()->getValue('system_title')).'">'; // Do not remove. This meta tag is acessed by oidplus_base.js
		}
		if ($theme_color != '') {
			$head_elems[] = '<meta name="theme-color" content="'.htmlentities($theme_color).'">';
		}
		$head_elems[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$head_elems[] = '<title>'.htmlentities($title).'</title>';
		$tmp = (OIDplus::insideSetup()) ? '?noBaseConfig=1' : '';
		$head_elems[] = '<script src="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'polyfill.min.js.php'.$tmp.'"></script>';
		$head_elems[] = '<script src="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'oidplus.min.js.php'.$tmp.'" type="text/javascript"></script>';
		$head_elems[] = '<link rel="stylesheet" href="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'oidplus.min.css.php'.$tmp.'">';
		$head_elems[] = '<link rel="icon" type="image/png" href="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'favicon.png.php">';
		if (OIDplus::baseConfig()->exists('CANONICAL_SYSTEM_URL')) {
		/*
			$head_elems[] = '<link rel="canonical" href="'.htmlentities(OIDplus::canonicalURL().OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL)).'">';
			*/
						$head_elems[] = '<link rel="canonical" href="'.rtrim(OIDplus::webpath(null, 
                               OIDplus::PATH_ABSOLUTE_CANONICAL),'/ ').$_SERVER['REQUEST_URI']
							.'">';
		}

		//$files[] = 'var csrf_token = '.js_escape($_COOKIE['csrf_token'] ?? '').';';
		$head_elems[] = '<script>var csrf_token = '.js_escape($_COOKIE['csrf_token'] ?? '').';</script>';		
		return $head_elems;
	}	
	
	
	public function showMainPage(string $page_title_1, string $page_title_2, string $static_icon, string $static_content, array $extra_head_tags=array(), string $static_node_id=''): string {
	//	$head_elems = (new OIDplusGui())->getCommonHeadElems($page_title_1);
		$head_elems = $this->getCommonHeadElems($page_title_1);
		$head_elems = array_merge($extra_head_tags, $head_elems);

		$plugins = OIDplus::getAllPlugins();
		foreach ($plugins as $plugin) {
			$plugin->htmlHeaderUpdate($head_elems);
		}

		# ---

		$out  = "<!DOCTYPE html>\n";

		$out .= "<html lang=\"".substr(OIDplus::getCurrentLang(),0,2)."\">\n";
		$out .= "<head>\n";
		$out .= "\t".implode("\n\t",$head_elems)."\n";
		$out .= "</head>\n";

		$out .= "<body>\n";

		$out .= '<div id="loading" style="display:none">Loading&#8230;</div>';

		$out .= '<div id="frames">';
		$out .= '<div id="content_window" class="borderbox">';

		$out .= '<h1 id="real_title">';
		if ($static_icon != '') $out .= '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		$out .= htmlentities($page_title_2).'</h1>';
		$out .= '<div id="real_content">'.$static_content.'</div>';
		if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] == 'GET')) {
			$out .= '<br><p><img src="img/share.png" width="15" height="15" alt="'._L('Share').'"> <a href="'
			//	.htmlentities(OIDplus::canonicalUrl($static_node_id))
				.htmlentities('?goto='.$static_node_id)
				.'" id="static_link" class="gray_footer_font">'._L('View repository page of entry').': '.htmlentities($static_node_id).'</a>';
			$out .= '</p>';
		}
		$out .= '<br>';

		$out .= '</div>';

		$out .= '<div id="system_title_bar">';

		$out .= '<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">';
		$out .= '	<div id="bar1"></div>';
		$out .= '	<div id="bar2"></div>';
		$out .= '	<div id="bar3"></div>';
		$out .= '</div>';

		$out .= '<div id="system_title_text">';
		$out .= '	<a '.OIDplus::gui()->link('oidplus:system').' id="system_title_a">';
		$out .= '		<span id="system_title_logo"></span>';
		$out .= '		<span id="system_title_1">'.htmlentities(OIDplus::getEditionInfo()['vendor'].' OIDplus 2.0').'</span><br>';
		$out .= '		<span id="system_title_2">'.htmlentities(OIDplus::config()->getValue('system_title')).'</span>';
		$out .= '	</a>';
		$out .= '</div>';

		$out .= '</div>';

		$out .= OIDplus::gui()->getLanguageBox($static_node_id, true);

		$out .= '<div id="gotobox">';
		$out .= '<input type="text" name="goto" id="gotoedit" value="'.htmlentities($static_node_id).'">';
		$out .= '<input type="button" value="'._L('Go').'" onclick="gotoButtonClicked()" id="gotobutton">';
		$out .= '</div>';

	
		$out .= '<div id="oidtree" class="borderbox">';
		//$out .= '<noscript>';
		//$out .= '<p><b>'._L('Please enable JavaScript to use all features').'</b></p>';
		//$out .= '</noscript>';
		$out .= OIDplus::menuUtils()->nonjs_menu();
		$out .= '</div>';
/*	*/
		
		
		$out .= '</div>';

		$out .= "\n</body>\n";
		$out .= "</html>\n";

		# ---

		$plugins = OIDplus::getAllPlugins();
		foreach ($plugins as $plugin) {
			$plugin->htmlPostprocess($out);
		}

		return $out;
	}

	 
}
