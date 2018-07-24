# PHP + Magento API Example
Click2Sync Webhooks API Implementation bootstrap template of a Magento2 integration with a PHP proxy/translator.

# Similar to
This implementation is similar to the Magento1, VTEX, Opencart, CSCart implementation

# Setup

1. Adapt the code to your needs, credentials, etc.
2. Upload the PHP script into a public path of yours (your website folder, a folder on a hosted web server, etc.)

# Example config

1. Hostname: http://www.example.com/
2. Products Endpoint: /c2sapi_webhooks_magento2_bootstrap.php?entity=products
3. Orders Endpoint: /c2sapi_webhooks_magento2_bootstrap.php?entity=orders

# Authentication

All hooks are requested with the authorization key header:

* Header name: C2SKey
* Header value: {{the private key you generated on the platform when you generated a connection to Click2Sync}}

You MUST validate that any call you receive to your endpoints are properly authenticated
You MUST rename the scripts/endpoints to avoid bots to discover your implementations

More info:
https://www.click2sync.com/developers/start.html

# Webhooks API Docs

https://www.click2sync.com/developers/api.html