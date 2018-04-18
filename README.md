# Simple OpenDota API library for PHP

### API version: 17.6.0

Simple OpenDota API support realization for PHP.

## Requirements

* PHP 5+
* cURL

## How to use

1. Include `simple_opendota.php` in your project
2. Create `new odota_api()` instance.

That's it. You can work with OpenDota API through `odota_api` methods. Every method returns associative array made out of JSON response.

You can find the list of methods and their API counterparts in [ENDPOINTS.md](ENDPOINTS.md).

## Important Notes

### Additional odota_api() parameters

Full version: `odota_api($cli_report_status, $hostname, $cooldown, $api_key)`

* `$cli_report_status`: (bool) report about every action to console. **Default: `false`**
* `$hostname`: (string) address of OpenDota API instance you're going to work with. **Default: `"https://api.opendota.com/api/"`**
* `$cooldown`: (int) API's cooldown between requests in milliseconds. **Default: `1000` or `200` if API key was specified** (approximately 1 per second)
* `$api_key`: (string) OpenDota API Key. **Default: none**

If you need to skip one of parameters, you can left it empty for default value.

### Work modes

Every method's last parameter is `$mode`. It may have one of three values:

* ` 0`: Safe mode, sleep until API is ready (default)
* ` 1`: Force mode, don't wait for API cooldown
* `-1`: Fast mode, drop request if API isn't ready

### API Endpoints with multiple parameters

API Endpoints with multiple GET parameters (for example, `/players/{account_id}/matches`) use additional parameter:

* `$params`: (array) array of parameters. Every key is parameter's name, every value is its value.

It's second to last for the methods of those endpoints. Parameters names are directly translated to API endpoint, so if you'll need them, just check OpenDota Docs and use the same names.

To see what methods use `$params` array and what don't, check [ENDPOINTS.md](ENDPOINTS.md) (or you can check the code itself).

## Example

```PHP
require "simple_opendota.php";

$od = new odota_api();

$res = $od->match(1234567902);

$od = new odota_api(true);

$res = $od->player(123123123);

$od = new odota_api(true, "localhost", 100);

$res = $od->live();
```
