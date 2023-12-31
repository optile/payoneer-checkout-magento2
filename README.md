# [Payoneer Checkout](https://checkoutdocs.payoneer.com/docs/integrate-with-magento/) Magento 2 extension

This library includes the Magento 2 Extension for Payoneer Checkout. The directories hierarchy is as positioned in a standard Magento 2 project library.

## Version

Payoneer Checkout Magento 2 extension version: 0.8.3

## Release Notes

### 0.8.3 - 2023-10-20

- Fixed PHP version issue in Magento 2.4.4

### 0.8.2 - 2023-10-19

- Admin can download log files.
- Few other minor bug fixes.

### 0.8.1 - 2023-09-27

- Default payment mode as Capture.

### 0.8 - 2023-09-26

- Compatability with Magento 2.4.6
- Add MoR (Merchant on Record) features
- Rename extension package name

### 0.7 - 2022-11-18

- Send system information in list session call

### 0.6 - 2022-11-01

- Fixed issue with locale sent to PSP list session
- Updated documentation

### 0.5 - 2022-09-30

- Compatibility with Magento 2.4.5
- Fixed issue with mini cart in case of failed transactions.
- Update list session for already existing sessions.
- Create new payment list session if the update session fails.
- Fixed issue with transaction page for captured orders.
- New Feature to display static payment icons.
- Handle notifications from Payoneer immediately.
- Compatibility with Magento 2.4.0
- Fixed issue with invoice of cancelled orders.
- Localization in Simplified and Traditional Chinese.
- Fixed issue with partial refund.

## Requirements

Magento versions 2.4.0 - 2.4.6

PHP versions 7.3, 7.4, 8.1

## Install via [composer](https://getcomposer.org/download/)

Run the following command under your Magento 2 root dir:

```cmd
composer require payoneer/payoneer-checkout
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:clean
```

## Install manually under app/code

1. Download and place the contents of this repository under {YOUR-MAGENTO-ROOT-DIR}/app/code/Payoneer/OpenPaymentGateway.
2. Run the following commands under your Magento 2 root dir:

```cmd
php bin/magento maintenance:enable
php bin/magento module:enable Payoneer_OpenPaymentGateway
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:clean
```

## Usage

After the installation, Go to the Magento 2 admin panel

Go to Stores -> Settings -> Configuration -> Sales -> Payment Methods -> Other Payment Methods -> Payoneer Checkout

![Payoneer Checkout Configuration](docs/img/payoneer_config.png)

Enable the payment gateway and choose whether it's Test environment or Live. Provide the corresponding merchant code and API key. If your Merchant account doesn't have a default Store code against it, provide it here.

## Advanced Configuration

![Payoneer Checkout Advanced Configuration](docs/img/payoneer_advanced_config.png)

You can set advanced configurations here.

## Style Configuration

![Payoneer Checkout Style Configuration](docs/img/payoneer_style_config.png)

You can set the style of the embedded widget from here. Please note that, styles defined in the field Custom CSS will override any conflicting configurations as per CSS specificity rules.
