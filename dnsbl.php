<?php

class dnsbl extends rcube_plugin
{
    public $task = 'login';

    // List of DNSBLs to check (you can add more if needed)
    private $dnsbl_lists = array(
    );

    // Whitelist and Blacklist Arrays (these could be stored in a database or file in a real-world scenario)
    private $whitelist = array(
    );  

    private $blacklist = array(
    );  


    public function init()
    {
        // Add the hook to the 'authenticate' event
        $this->add_hook('authenticate', [$this, 'authenticate']);
	include_once(__DIR__ . '/config.inc.php');

// 2. Set the DNSBL lists from the config file
	$this->dnsbl_lists = isset($config['dnsbl_lists']) ? $config['dnsbl_lists'] : array();

// 3. Set the whitelist from the config file
	$this->whitelist = isset($config['whitelist']) ? $config['whitelist'] : array();

// 4. Set the blacklist from the config file
	$this->blacklist = isset($config['blacklist']) ? $config['blacklist'] : array();

// 5. (Optional) Log an error if the config file is missing
	if (!file_exists(__DIR__ . '/config.inc.php')) rcube::write_log("errors", "Plugin dnsbl: config.inc.php file not found!");
    }


    public function authenticate($args)
    {
        // Get the real IP address of the user
        $userip = $this->dnsbl_getVisitorIP();

        // Check if the IP is in the blacklist
        if ($this->is_ip_blacklisted($userip)) {
            $this->block_user($userip, "blacklist");
            $args['_action'] = ''; // Prevent login action
            return $args;
        }

        // Check if the IP is in the whitelist
        if ($this->is_ip_whitelisted($userip)) {
            return $args;  // Allow login if the IP is whitelisted
        }

        // Check if the IP is blacklisted in DNSBL
        if ($this->dnsbl_blacklisted($userip)) {
            $this->block_user($userip, "DNSBL");
            $args['_action'] = ''; // Prevent login action
            return $args;
        }

        return $args;
    }

    // Function to get the real IP address of the visitor
    public function dnsbl_getVisitorIP()
    { 
        // Check if the IP is coming from a proxy (HTTP_X_FORWARDED_FOR header)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // If there are multiple IPs in the X-Forwarded-For header, take the first one
            $visitorIP = strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ',');
        } else {
            // Fallback to REMOTE_ADDR if X-Forwarded-For is not available
            $visitorIP = $_SERVER['REMOTE_ADDR'];
        }
        $ip_regexp = "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/";
        if (!preg_match($ip_regexp, $visitorIP)) {
            // Handle invalid IP format (optional)
            echo "<script>alert('Invalid IP format detected. Please check your connection.');</script>";
            return false; // Return false or handle as needed
        }
        return $visitorIP; 
    }

    // Function to check if the IP is blacklisted in DNSBL
    public function dnsbl_blacklisted($ip)
    {
        // Reverse the IP address for DNSBL query
        $reverse_ip = implode(".", array_reverse(explode(".", $ip)));

        // Loop through each DNSBL list
        foreach ($this->dnsbl_lists as $dnsbl_list) {
            if (function_exists("checkdnsrr")) {
                // Check if the reversed IP is listed in the DNSBL
                if (checkdnsrr($reverse_ip . "." . $dnsbl_list . ".", "A")) {
                    // Return true if blacklisted
                    rcube::write_log("errors", "Plugin dnsbl blocked: the IP is blocked due to being in DNS blacklist - IP: " . $ip);
                    return true;
                }
            }
        }

        // Return false if the IP is not blacklisted
        return false;
    }

    // Function to check if the IP is in the whitelist
    public function is_ip_whitelisted($ip)
    {
        foreach ($this->whitelist as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    // Function to check if the IP is in the blacklist
    public function is_ip_blacklisted($ip)
    {
        foreach ($this->blacklist as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    // Function to check if an IP is within a given range (CIDR)
    public function ip_in_range($ip, $range)
    {
        // If the range is in CIDR format, check the IP against it
        if (strpos($range, '/') !== false) {
            list($subnet, $mask) = explode('/', $range);
            $subnet_dec = ip2long($subnet);
            $ip_dec = ip2long($ip);
            $mask_dec = ~((1 << (32 - $mask)) - 1);

            return ($subnet_dec & $mask_dec) == ($ip_dec & $mask_dec);
        } else {
            // If it's a single IP, check equality
            return $ip === $range;
        }
    }

    // Function to block the user and display the error message
    public function block_user($userip, $reason)
    {
        // Display the error message
	echo "<html><head>
<!-- Remove comment to autoplay some sound.
        <script>
            window.onload=function(){
                var audio = new Audio('/plugins/dnsbl/dennis_nedry_ahahah.mp3'); // Replace with your MP3 file path
                audio.loop = true; 
                audio.play();
            }
        </script>
--!>
        <style>
            body{margin:0;padding:0;height:100vh;display:flex;justify-content:center;align-items:center;background-color:#f8d7da}
            .container{font-size:30px;font-weight:bold;color:#721c24;text-align:center;padding:20px;width:80%;max-width:600px;}
        </style>
    </head><body>
        <div class='container'>
<!-- Remove comment to show image            <img src='http(s)://www.example.com/excited.gif' alt='Excited GIF'> --!>
            <p>THE IP YOU ARE VISITING FROM IS CONSIDERED BLACKLISTED! YOU CAN'T LOGIN FROM HERE! IP:  ".$userip."</p>
        </div>
    </body></html>";
        // Log the blocked IP attempt
        rcube::write_log("errors", "Plugin dnsbl blocked: the IP is blocked due to being in the " . strtoupper($reason) . " - IP: " . $userip);

        // Logout and kill session
        $rcmail = rcmail::get_instance();
        $rcmail->logout_actions();
        $rcmail->kill_session();

        // Redirect to a blocked page, passing the user's IP in the URL
//        header('Location: /plugins/dnsbl/dnsbl.html?ip=' . urlencode($userip));
        exit;
    }
}

?>
