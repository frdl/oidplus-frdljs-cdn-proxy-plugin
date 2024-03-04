# OIDplus CDN Proxy Plugin
Frdlweb.js and CDN-Proxy Plugin for OIDplus.

- Optionally include and configure the Frdlweb.js framework for your OIDplus
- Include a cached proxy to a configurable CDN (default: cdn.startdir.de) for npm/assets on your local OIDplus instance

# Installation
There are 2 ways to install the plugin:

### Composer
If [this package](https://github.com/frdl/oiplus-composer-plugin) is installed in the root composer.json of your OIDplus instance you can require the plugin with composer.

`composer require frdl/oidplus-frdljs-cdn-proxy-plugin`

### Manually
Create the directory
`<OIDplus_ROOT>/plugins/frdl/publicPages/cdn/`
Unzip this git archive into this directory.
