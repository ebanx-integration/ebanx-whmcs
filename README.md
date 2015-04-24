# EBANX WHMCS Payment Gateway Extension

This plugin allows you to integrate your WHMCS store with the EBANX payment gateway.
It includes support to peruvian payment methods PagoEfectivo and SafetyPay.

## Requirements

* PHP >= 5.3
* cURL
* WHMCS >= 5.3.11

## Installation
### Source
1. Clone the git repo to your WHMCS root/modules/gateways folder
```
git clone --recursive https://github.com/ebanx/ebanx-whmcs.git
```
2. Visit your WHMCS payment settings at **Setup > Payments > Payment Gateways**.
3. Locate module **EBANX - Boleto Bancário, TEF, PagoEfectivo, SafetyPay** and click "Activate".
4. Add the integration key you were given by the EBANX integration team. You will need to use different keys in test and production modes.
5. Change the other settings if needed. Click on "Save Changes".
6. Go to the EBANX Merchant Area, then to **Integration > Merchant Options**.
  1. Change the _Status Change Notification URL_ to:
```
{YOUR_SITE}/modules/gateways/ebanx/callback.php
```
  2. Change the _Response URL_ to:
```
{YOUR_SITE}/modules/gateways/ebanx/ebanx_response.php
```
7. That's all!

## Changelog
* 1.2.0: Several improvements
* 1.1.2: Order number workaround
* 1.1.1: Corrected declarations to fit all PHP versions
* 1.1.0: Added Mexico. Handled new notifications
* 1.0.0: first release.