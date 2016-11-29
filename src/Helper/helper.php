<?php

if (!function_exists('ip2int')) {
    /**
     * @param string $ip
     * @return int
     */
    function ip2int($ip)
    {
        $ip = explode('.', $ip);
        return
            (intval($ip[0])<<24 | intval($ip[1])<<16 | intval($ip[2])<<8 | intval($ip[3]));
    }
}

if (!function_exists('int2ip')) {
    /**
     * @param int $ip
     * @return string
     */
    function int2ip($ip)
    {
        return
            ((string) ($ip & 0xff000000)>>24) . '.' .
            ((string) ($ip & 0x00ff0000)>>16) . '.' .
            ((string) ($ip & 0x0000ff00)>>8) . '.' .
            ((string) $ip & 0x000000ff);
    }
}
