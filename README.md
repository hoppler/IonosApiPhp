# Breaking: 1und1 Login-Page has changed
Login is not possible anymore with the current code

# IonosApiPhp
Provide DynDns-Service for Ionos/1und1 DNS with PHP

## Description
Call to index.php handles a GET-Request with the parameters authKey, ip and domain.
* **authKey** is a constant string defined in settings.json and has to be the same was in settings.json
* **ip** is the new ipAdress and gets validated: 127.0.0.1
* **domain** is the domain/subdomain you want to set the A-DNS entry for: subodmain.domain.com (has to be in _allowedDomains_)

Only domains in the _allowedDomains_ array of settings.json can be used as updateable domains (to prevent damage by wrongly sent domains)

## Attention
Please secure the access to settings.json
### htaccess Example


```xml
Order deny,allow
Deny from all

<Files index.php>
    Order Allow,Deny
    Allow from all
</Files>
```

## Configuration per settings.json
```json
{
  "username": "",
  "password": "",
  "rootDomain" : "domain.de",
  "isWwwRecord": false,
  "ttl": 300,
  "authKey": "key",
  "allowedDomains": ["subdomain.domain.de"]
}
```

## Usage FritzBox
Set URL to: 
* http(s)://phpScriptHostingDomain.com/index.php?authKey=**key**&domain=**\<domain\>**&ip=**\<ipaddr\>**

**\<domain\>** and **\<ipaddr\>** are replaced by the FritzBox with the new ip and the domain value in the inputfield under the URL
