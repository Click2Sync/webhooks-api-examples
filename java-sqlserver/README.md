# Java + SQLServer Example
Click2Sync Webhooks API Implementation bootstrap template of a Java Server connected to a SQL Server Database.

# Setup

1. Adapt the code to your needs, credentials, etc.
2. Run the server on a machine with a public IP / hostname / reverse proxy that can be seen from the internet

# Example config

1. Hostname: http://www.example.com:8000/
2. Products Endpoint: /api/products
3. Orders Endpoint: /api/orders

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