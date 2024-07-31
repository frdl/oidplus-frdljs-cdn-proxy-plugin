<?php


namespace Frdlweb\OIDplus\Plugins\PublicPages\CDNProxy;



use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Plugins\ObjectTypes\OID\WeidOidConverter;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Attachments\OIDplusPagePublicAttachments;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;


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
		$this->cdnCacheExpires = max(3 * 60 * 60, intval(OIDplus::baseConfig()->getValue('CDN_CACHE_EXPIRES', 24 * 60 * 60 )));
	}


	public function init($html = true): void {



		$io4Plugin = OIDplus::getPluginByOid("1.3.6.1.4.1.37476.9000.108.19361.24196");
		if (!is_null($io4Plugin) && \is_callable([$io4Plugin,'getWebfat']) ) {
		   $io4Plugin->getWebfat(true,false);
		}else{
			throw new OIDplusException(sprintf('You have to install the dependencies of the plugin package %s via composer OR you need the plugin %s to be installed in OIDplus and its remote-autoloader enabled. Read about how to use composer with OIDplus at: https://weid.info/plus/ .', 'https://github.com/frdl/oidplus-frdlweb-rdap', 'https://github.com/frdl/oidplus-io4-bridge-plugin'));
		}

		OIDplus::config()->prepareConfigKey('FRDLWEB_CDN_RELATIVE_URI', 'The CDN base uri to the CDN/Proxy -Module. Example: "cdn/" or "assets/"', self::DEFAULT_CDN_BASEPATH, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {

			 	OIDplus::baseConfig()->setValue('FRDLWEB_CDN_RELATIVE_URI', $value );
		});

		OIDplus::config()->prepareConfigKey('FRDLWEB_DEFAULT_JS_CONFIG_QUERY', 'Set the config query for Frdlweb.js. Set to false to disable!  Help: https://frdl.de/blog/view/1208/webfanjs-frdlwebjs-configuration-parameters', self::DEFAULT_JS_CONFIG_QUERY, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
			//if (($value != '') && !oid_valid_dotnotation($value, false, false, 1)) {
		//		throw new OIDplusException("Please enter a valid OID in dot notation or nothing");
		//	}
			 $file = __DIR__.\DIRECTORY_SEPARATOR.'webfan.js~cache.js';
			if(file_exists($file)){
			 unlink($file);
			}
		//	OIDplus::baseConfig()->setValue('FRDLWEB_DEFAULT_JS_CONFIG_QUERY', $value );
		});

		OIDplus::config()->prepareConfigKey('FRDLWEB_CDN_PROXY_TARGET_BASE', 'The CDN base uri to get/proxy from/to. Can be e.g.: "https://cdn.startdir.de" or "https://cdn.frdl.de" or "https://cdn.webfan.de"', self::DEFAULT_CDN_MASTER_BASEURI, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {

			if(!in_array($value, ['https://cdn.startdir.de', 'https://cdn.frdl.de', 'https://cdn.webfan.de', ])){
				throw new OIDplusException('Can be e.g.: "https://cdn.startdir.de" or "https://cdn.frdl.de" or "https://cdn.webfan.de"');
			}


			$file = __DIR__.\DIRECTORY_SEPARATOR.'webfan.js~cache.js';
			if(file_exists($file)){
			 unlink($file);
			}
		//	OIDplus::baseConfig()->setValue('FRDLWEB_CDN_PROXY_TARGET_BASE', $value );
		});

		if(true !== $html){
			self::prune($this->cdnCacheDir, $this->cdnCacheExpires, true, false);
		}
	}



    public static function prune($dir, $limit, $skipDotFiles = true, $remove = false)
    {
        $iterator = new \DirectoryIterator($dir);
         $c = 0;
         $all = 0;
         foreach ($iterator as $fileinfo) {
        if ($fileinfo->isFile()) {
        $c++;
        if(true===$skipDotFiles && '.'===substr($fileinfo->getFilename(),0,1))continue;
             // if($fileinfo->getMTime() < time() - $limit){
        if(filemtime($fileinfo->getPathname()) < time() - $limit){
            if(file_exists($fileinfo->getPathname()) && is_file($fileinfo->getPathname())
                && strlen(realpath($fileinfo->getPathname())) > strlen(realpath($dir))
              ){
                //  echo $fileinfo->getPathname();
            //  @chmod(dirname($fileinfo->getPathname()), 0775);
            //  @chmod($fileinfo->getPathname(), 0775);
                unlink($fileinfo->getPathname());
                $c=$c-1;
            }
        }
        }elseif ($fileinfo->isDir()){
             $firstToken = substr(basename($fileinfo->getPathname()),0,1);
               if('.'===$firstToken)continue;

            $subdir = rtrim($fileinfo->getPathname(),'/ ') . DIRECTORY_SEPARATOR;
            $all += self::prune($subdir, $limit, $skipDotFiles, true);

        }
         }//foreach ($iterator as $fileinfo)

        if(true === $remove && 0 === max($c, $all)){
         @rmdir($dir);
        }

        return $c;
    }



	protected function cdn_write_cache(string $out, string $cacheFile){
		if(!is_dir(dirname($cacheFile))){
		 mkdir(dirname($cacheFile), 0755, true);
		}
		file_put_contents($cacheFile, $out);
	//	chmod($cacheFile, 0755);
		touch($cacheFile);
	}

	/**
	 * @param string $cacheFile
	 * @param int $rdapCacheExpires
	 * @return array|null
	 */
	protected function cache_read_serve(string $cacheFile, int $rdapCacheExpires){
		if (file_exists($cacheFile) && ( $rdapCacheExpires < 1 || filemtime($cacheFile) >= time() - $rdapCacheExpires) ) {
			     $test=explode('.', $cacheFile);
		          $ext = $test[count($test)-1];
			      $e =[
					'vue' => 'application/octet-stream',

				  ];
            	\VtsBrowserDownload::output_file($cacheFile, isset($e[$ext]) ? $e[$ext] : '', 1);
			return true;
		}
		return false;
	}

   public function handle404(string $request): bool {
	 	//die($request);
	    $CDN_BASEPATH =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_RELATIVE_URI', self::DEFAULT_CDN_BASEPATH );
	    $BASE_URI = rtrim(OIDplus::webpath(OIDplus::localpath(),OIDplus::PATH_ABSOLUTE_CANONICAL), '/ ').'/'.trim($CDN_BASEPATH, '/ ').'/';
	   $rel_url_original =substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));

	   /*
	    if(!str_starts_with($request, $CDN_BASEPATH)){
			print_r([$request, $CDN_BASEPATH, $rel_url_original]);
			die();
			return false;
		}
		*/
        $request = $rel_url_original;

	   $baseHref = !isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? (OIDplus::isSSL()
															   ? 'https://'
															   : 'http://')
															  .$_SERVER['SERVER_NAME'].OIDplus::webpath(null, OIDplus::PATH_RELATIVE)
		   : OIDplus::baseConfig()->getValue('CANONICAL_SYSTEM_URL', OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL) );

	   $baseHref = rtrim($baseHref, '/ ').'/';
	   ob_start();
	   originHeaders();
	   OIDplus::invoke_shutdown();


	    //$isCdnUri = preg_match('@^/'.preg_quote($request,'@').'/(.+)$@', $_SERVER['REQUEST_URI'], $m);
	    $isCdnUri = str_starts_with($request, $CDN_BASEPATH)
			&& (
			preg_match('/'.preg_quote($CDN_BASEPATH,'/@').'(?P<oid>([0-9\.^\/]+))?(?P<uri>(.+))/', str_replace('oid:', '', $_SERVER['REQUEST_URI']), $matches)
		//	|| preg_match('/'.preg_quote($CDN_BASEPATH,'/@').'(?P<id>([^\/]+))\/(?P<uri>(.+))/', $_SERVER['REQUEST_URI'], $matches)
		 );

	   if(!isset($matches))return false;
	       $uri = ltrim($matches['uri'], '/ ');
	       $p = explode('/', $uri);
           $id = $p[0];
	      if(str_starts_with($id, 'weid:')){
	          $oid =  'oid:'.WeidOidConverter::weid2oid($id);
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
				&& !OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.1.95', false)  // OIDplusPagePublicAttachments disabled?

			   ){
				$uploaddir = OIDplusPagePublicAttachments::getUploadDir($id);

				if((false===$file || empty($file) || ''===$file) || '/' === substr($uri, strlen($uri)-1,1) ){
				  $files = glob($uploaddir
								. DIRECTORY_SEPARATOR
								//  .'/'
								 . '*');






					  //$linkDirectory = $BASE_URI.$objGoto->getIriNotation(false).'/';
					  $linkDirectory = $BASE_URI.$this->getObjectsWebpathNotation($objGoto).'/';
					  $permaLinkDirectory = $BASE_URI.$id.'/';

					  $links = [];
						foreach($files as $f){
							$links[$BASE_URI
						             .trim(strtolower($this->getObjectsWebpathNotation($objGoto)), '/ ')
								 //  .$objGoto->getIriNotation(false)
								.'/'.basename($f) ] =
								$BASE_URI
						      //.strtolower($objGoto->getIriNotation(false))
								  .trim($this->getObjectsWebpathNotation($objGoto), '/ ')
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
																 '<base href="'.$baseHref.'">' ,

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
																 '<base href="'.$baseHref.'" />',
																 ],
					   $id
				   );

				   echo $pageHTML;
					// return true;
					die();
	            }elseif($id && is_object($objGoto) && ( OIDplusObject::exists($id)
													 //  && OIDplusObject::exists('oid:'.$oid)
													  ) && file_exists($local_file)){//404 from local OID attachments dir

				   $filename = basename($local_file);
					if (strpos($filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
			    	if (strpos($filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
				    if (strpos($filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
				    if (strpos($filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));

				   if(!$objGoto->userHasReadRights() ){
					    throw new OIDplusException(_L(sprintf('You have no access to %s, please login!',$filename)));
				   }


				  if(!$this->cache_read_serve($local_file, -1)){
					 throw new OIDplusException(_L(sprintf('The file %s is not available [1].',$filename)));
				 }else{
					 die();
					 return false;
				 }
			   }
			}//$objGoto




		   $CDN_TARGET_BASE =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_PROXY_TARGET_BASE', self::DEFAULT_CDN_MASTER_BASEURI );
			//	$url = rtrim($CDN_TARGET_BASE, '/ ').'/'.$uri.'?'.$_SERVER['QUERY_STRING'];
				$q = $_GET;
				unset($q['h404']);
		        ksort($q);
				$url = rtrim($CDN_TARGET_BASE, '/ ').'/'.$uri.'?'. \http_build_query($q, '', '&', \PHP_QUERY_RFC3986);

		        $hp = json_encode($q);

		    $file = $this->cdnCacheDir . str_replace('/', \DIRECTORY_SEPARATOR, explode('?', $uri)[0]);
		    $filename = 'h-'.sha1($hp).'-l'.strlen($hp).'.'.basename($file);

		   	$test=explode('.', $filename);
		    $ext = $test[count($test)-1];

		   if('php' === $ext){
			   throw new OIDplusException(_L(sprintf('The file %s is not wanted!',$filename)));
		   }

					if (strpos($filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
			    	if (strpos($filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
				    if (strpos($file, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
				    if (strpos($filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));


		    if(!$this->cache_read_serve($file,intval( $this->cdnCacheExpires ) )){

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
																 '<base href="'.$baseHref.'" />',
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
					 throw new OIDplusException(_L(sprintf('The file %s is not available [%s].',$filename, __LINE__)));
				 }else{
					 return die();
				 }
			}


	   }


    return false;
   }





	public function getObjectsWebpathNotation($objGoto){
		if(is_callable([$objGoto, 'getIriNotation'])){
			return $objGoto->getIriNotation(false);
		}

		$del = substr( $objGoto->crudInsertPrefix(), -1);

		$str = str_replace($del, '/', $objGoto->nodeId(false));
		return $str;
	}


	public function modifyContent($id, &$title, &$icon, &$text): void {

		$CDN_BASEPATH =	OIDplus::baseConfig()->getValue('FRDLWEB_CDN_RELATIVE_URI', self::DEFAULT_CDN_BASEPATH );
	    $BASE_URI = rtrim(OIDplus::webpath(OIDplus::localpath(),OIDplus::PATH_ABSOLUTE_CANONICAL), '/ ').'/'.trim($CDN_BASEPATH, '/ ').'/';

		    $payload = '<br /> <a href="'.$BASE_URI.$id.'/" class="gray_footer_font">'._L('CDN/Files').'</a>';

		$text = str_replace('<!-- MARKER 6 -->', '<!-- MARKER 6 -->'.$payload, $text);

	}




 	// public function action(string $actionID, array $params): array {
   //       return parent::action($actionID, $params);
 	// }







/*
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


	*/
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
		//$files[] = 'var samesite_policy = '.js_escape(OIDplus::baseConfig()->getValue('COOKIE_SAMESITE_POLICY','Strict')).';';
		$head_elems[] = '<script>var csrf_token = '.js_escape($_COOKIE['csrf_token'] ?? '').';</script>';
		$head_elems[] = '<script>var csrf_token_weak = '.js_escape($_COOKIE['csrf_token_weak'] ?? '').';</script>';
		$head_elems[] =
			'<script>var samesite_policy = '.js_escape(OIDplus::baseConfig()->getValue('COOKIE_SAMESITE_POLICY','Strict')).';</script>';
		return $head_elems;
	}


	public function showMainPage(string $page_title_1, string $page_title_2, string $static_icon, string $static_content, array $extra_head_tags=array(), string $static_node_id=''): string {


		$_REQUEST['goto'] = $static_node_id;

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
		$out .= '<input type="text" name="goto" id="gotoedit" value="'.$static_node_id.'">';
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



   public function gui($id, &$out, &$handled): void {
		 if('oidplus:home'===$id){
			 /*
			 header('Location: '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) );
			 die('<a href="'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'">
			 Go to: '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)
				 .'</a>'
		     );
			 */
			 header('Location: '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) );
			 $io4Plugin = OIDplus::getPluginByOid("1.3.6.1.4.1.37476.9000.108.19361.24196");
			 $out['text']  = $io4Plugin->handle404('/');
			 $handled = true;
			  flush();
			   die($out['text']);
		 }
     }



	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {

		if (file_exists(__DIR__ . '/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__) . 'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$Array = (new \Wehowski\Helpers\ArrayHelper($json)) ;
		$Array->after(0)->add([
		    'id' => 'oidplus:home',
		 	'icon' => $tree_icon,
			// 'a_attr'=>[
			// 	 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			 // ],
			 // 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			'text' => _L('Home'),
	   ]);

			$json = $Array->all();
		return true;
		 /*

		$json[] = [
			'id' => 'oidplus:system',
		//	 'icon' => $tree_icon,
			'text' => _L('Registry'),
	      ];


		$json[] = [
		    'id' => 'oidplus:home',
		//	'icon' => $tree_icon,
			'text' => _L('Home'),
	   ];

			return true;


		$json[] = [
			'id' => 'oidplus:system',
			 'icon' => $tree_icon,
			'text' => _L('Registry'),
	      ];


		$json[] = [
		    'id' => 'oidplus:home',
			'icon' => $tree_icon,

			'text' => _L('Home'),
	   ];

		return false;

		$Array = (new \Wehowski\Helpers\ArrayHelper($json))
		->after(0)
		->add([
		    'id' => 'oidplus:home',
			'icon' => $tree_icon,
			// 'a_attr'=>[
			// 	 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			 // ],
			 // 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			'text' => _L('Home'),
	   ]) ->after(0)
			->add([
			'id' => 'oidplus:system',
			//'icon' => $tree_icon,
			'text' => _L('Registry'),
	      ]);


		$json = $Array->all();

	  	 array_unshift($json,array(
		    'id' => 'oidplus:home',
			'icon' => $tree_icon,
			// 'a_attr'=>[
			// 	 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			 // ],
			 // 'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			'text' => _L('Home'),
		),array(
			'id' => 'oidplus:system',
			//'icon' => $tree_icon,
			'text' => _L('Registry'),
		));


	*/

	 //	if (!OIDplus::authUtils()->isAdminLoggedIn()) return true;


/*
		$json[] = array(
			'id' => self::PAGE_ID_COMPOSER,
			//'icon' => $tree_icon,
			'text' => _L('Composer Plugins'),
		);


		$json[] = array(
			'id' => self::PAGE_ID_WEBFAT,
			//'icon' => $tree_icon,
			'text' => _L('Webfan Webfat Setup'),
			//'href'=>$this->getWebfatSetupLink(),
		);

		$json[] = array(
			'id' => self::PAGE_ID_BRIDGE,
			//'icon' => $tree_icon,
			'text' => _L('Webfan IO4 Bridge'),
		);

		return true;		*/
	}


	 public function publicSitemap(&$out): void {
		//$out[] = OIDplus::getSystemUrl().'?goto='.urlencode('com.frdlweb.freeweid');
	  //	 $out[] = OIDplus::getSystemUrl().'?goto='.urlencode('oidplus:system');
	  $out[] =OIDplus::webpath(null).'?goto='.urlencode('oidplus:home');
	 }


/*
	public function tree(array &$json, ?string $ra_email = null, bool $nonjs = false, string $req_goto = ''): bool {

		if (file_exists(__DIR__ . '/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__) . 'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			// Marschall 13.04.2023 wieder umgeändert
			'id' => 'com.frdlweb.freeweid',
			// 'id' => 'oidplus:com.viathinksoft.freeoid',
			'icon' => $tree_icon,
			'text' => str_replace('OID', 'WEID as OID Arc', _L('Register a free OID')),
		);

		return true;
	}
 */
	public function tree_search($request) {
		$ary = array();

		if ($obj = OIDplusObject::parse($request)) {
			if ($obj->userHasReadRights()) {
				/*
                do {
					$ary[] = $obj->nodeId();
				} while ($obj = $obj->getParent());
				*/


				while ($obj = $obj->getParent()) {
					$ary[] = $obj->nodeId();
				}

				$ary = array_reverse($ary);
			}
		}
		return $ary;
	}


}
