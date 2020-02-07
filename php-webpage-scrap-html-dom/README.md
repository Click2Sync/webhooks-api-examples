# PHP + Simple Html Dom Custom Website
Click2Sync Webhooks API Implementation bootstrap template of a PHP + Simple Html Dom Custom Website

# Setup

1. Adapt the code to your needs, credentials, etc.
2. Upload the PHP script into a public path of yours (your website folder, a folder on a hosted web server, etc.)

# Example config

1. Hostname: http://www.example.com/
2. Products Endpoint: /webpage-scrap-html-dom-products-adapter.php?entity=products

# Requirements

This code has as dependency the library "PHP Simple HTML DOM Parser" which can be downloaded here: 
https://simplehtmldom.sourceforge.io/

The file should be called something like "simple_html_dom.php" and be included on the script, as it assumes the library is in some place near the project.

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