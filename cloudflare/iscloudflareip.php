<?php
/**
 * Eric Draken
 * Date: 2016-08-30
 * Time: 7:22 PM
 * Desc: Check if a supplied IP is from cloudflare or not.
 *       Use memcached to cache Cloudflare IPs to avoid repolling
 * Usgae: iscloudflareip.php?ip=x.x.x.x
 * Retrun: JSON string containing IP, result, and the CF IP range if successful
 */

// The cloudflare list of v4 IPs
$cf_ips_source = 'https://www.cloudflare.com/ips-v4';

// Get the supplied IP as ?ip=127.0.0.1
$ip = filter_var(strip_tags(trim(@$_GET['ip'])), FILTER_SANITIZE_STRING);
if(filter_var($ip, FILTER_VALIDATE_IP) === false) {
    die("[]");
}

// Get the Cloudflare IP addresses
$key = 'CF_IPS';
$cacheseconds = 60 * 60 * 24;   // One day
$memcache = new Memcached();
$cf_ips = false;
if($memcache->addServer('localhost', 11211)) {
    if($cf_ips = $memcache->get(md5($key))) {
        // Sanity check
        $cf_ips = @json_decode($cf_ips, true);
        if(!is_array($cf_ips)) {
            throw new Exception("Saved Cloudflare IPs were not decoded properly. Memcached tampering?");
        }
    } else {
        // Get the current list of cloudflare IPs
        $cf_ips = array_map('trim', @file($cf_ips_source));
        if(is_array($cf_ips)) {
            // Save the arrays
            if(!$memcache->set(md5($key), json_encode($cf_ips), $cacheseconds)) {
                throw new Exception("Memcached couldn't save the IPs");
            }
        } else {
            throw new Exception("Cloudflare IPs are strange. Bad list returned?");
        }
    }
} else {
    throw new Exception("Memcached not working");
}

// 2) Check the supplied IP against the list above
$range = isCloudflareIP($ip, $cf_ips);
$json = new stdClass();
$json->ip = $ip;
$json->result = $range ? true : false;
$json->cfip = $range;

die( json_encode($json) );


//// FUNCTIONS /////

// REF: http://stackoverflow.com/a/38298029/1938889
function ip_in_range($ip, $range) {
    if (strpos($range, '/') == false)
        $range .= '/32';

    // $range is in IP/CIDR format eg 127.0.0.1/24
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

// REF: http://stackoverflow.com/a/38298029/1938889
function isCloudflareIP($ip, &$cf_ips) {
    $is_cf_ip = false;
    foreach ($cf_ips as $cf_ip) {
        if (ip_in_range($ip, $cf_ip)) {
            $is_cf_ip = $cf_ip;
            break;
        }
    } return $is_cf_ip;
}
