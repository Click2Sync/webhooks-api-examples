# webhooks-api-examples
Click2Sync Webhooks API Implementation bootstrap templates of frequently needed integrations

# Proxy
You can think of this kind of implementations as a proxy middleman that is translating/transforming the data structures from one side to the standard structure of the other.

# Hooks that can be received from Click2Sync

1. GET {{URI_PRODUCTS}}
    * with offset (int)
    * with sortorder param (asc, desc)
    * with id param (string)
    * with next param (step string tag)

2. GET {{URI_ORDERS}}
    2.1. with offset (int)
    2.2. with sortorder param (asc, desc)
    2.3. with id param (string)
    2.4. with next param (step string tag)

3. POST {{URI_PRODUCTS}}
    3.1. without id (CREATE)
    3.2. with id (UPDATE)

4. POST {{URI_ORDERS}}
    4.1. without id (CREATE)
    4.2. with id (UDPATE)

# Authentication

All hooks are requested with the authorization key header:

* Header name: C2SKey
* Header value: {{the private key you generated on the platform when you generated a connection to Click2Sync}}

You MUST validate that any call you receive to your endpoints are properly authenticated
You MUST rename the scripts or endpoints to avoid bots to discover your implementations

More info:
https://www.click2sync.com/developers/start.html

# Webhooks API Docs

https://www.click2sync.com/developers/api.html
