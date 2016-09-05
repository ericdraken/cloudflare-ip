# cloudflare-ip
Check if a supplied IP is from cloudflare or not. We use memcached to cache Cloudflare IPs to avoid over-polling.

### Usage: 

`/cloudflare/iscloudflareip.php?ip=108.162.238.207`

This will return a JSON string containing the supplied IP, result, and the CF IP range if successful. For example:

`{"ip":"108.162.238.207","result":true,"cfip":"108.162.192.0\/18"}`

or this upon failure:

`{"ip":"101.162.238.207","result":false,"cfip":false}`
