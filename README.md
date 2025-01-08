# dnsbl
Plugin to check users logging in from DNSBL lists for Roundcube
# Roundcube DNSBL Plugin

This is a Roundcube plugin to check users logging in from DNS Blacklists (DNSBL). It helps prevent login attempts from IP addresses that are listed in DNSBLs, providing an additional layer of security to your Roundcube installation.

## Features
- Blocks login attempts from IPs listed in DNSBLs.
- Allows configuring a custom list of DNSBLs, whitelist, and blacklist IP addresses.
- Fully configurable using a `config.inc.php` file for easy management.
- Extra feauture of showing image and playing sound can be configured in dnsbl.php
## Requirements
- Roundcube 1.6.8 or higher
- PHP 8.1 or higher

## Installation

### 1. Install via Composer

You can install the plugin via Composer by running:

```bash
composer require roundcube/dnsbl
### 1. Install manual
Clone this git to /roundcube_root/plugins/dnsbl

And add dnsbl to main config file plugin activation
