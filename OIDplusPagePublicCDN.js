(async(w, d)=>{
 var s=d.createElement('script');
 s.setAttribute('src',
				//document.currentScript.src 
				w.oidplus_webpath_absolute_canonical + 'plugins/frdl/publicPages/cdn/OIDplusPagePublicCDN.js' + '.php');	
 s.async='defer';
 d.head.appendChild(s);	
})(window, document);
