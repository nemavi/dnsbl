<?php

// Define whether DNSBL checks are enabled
$config['dnsbl_enabled'] = true;

// List of DNSBLs to check
$config['dnsbl_lists'] = array(
    "singular.ttk.pte.hu",  // Example DNSBL list
    // Add more DNSBLs here if needed
);

// Define whitelist IP ranges (CIDR format or individual IPs)
$config['whitelist'] = array(
    '192.168.0.0/16',  // Private Class C range
    '172.16.0.0/12',   // Private Class B range
    '127.0.0.1',
//    '10.0.7.108',      // Private Class A range
);

// Define blacklist IP ranges (CIDR format or individual IPs)
$config['blacklist'] = array(
     '10.0.7.108',      // Example IP
    // '193.6.62.0/24',   // Example range
);

?>
