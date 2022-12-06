# Mage2 Module MagTun Seofilterurl
    ``magtun/module-seofilterurl``
 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Seofilterurl is a module for Magento 2 to make the URLs from the layered filter navigation human readable and SEO-friendly.

## Installation
For information about a module installation in Magento 2, see [Enable or disable modules](https://devdocs.magento.com/guides/v2.4/install-gde/install/cli/install-cli-subcommands-enable.html).

\* = in production please use the `--keep-generated` option

### Type 1: Zip file
 - Unzip the zip file in `app/code/MagTun`
 - Enable the module by running `php bin/magento module:enable MagTun_Seofilterurl`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Apply generate class `php bin/magento setup:di:compile`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer
 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require magtun/module-seofilterurl`
 - enable the module by running `php bin/magento module:enable MagTun_Seofilterurl`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Apply generate class `php bin/magento setup:di:compile`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration
If your installation is complete you can navigate to your Administration Panel. Go to System > Configuration > Catalog.
Here you will find a new tab called “Search Engine Optimizations”.
In case you are not seeing the tabs, please flush your cache and relogin to the Adminpanel. (System > Cache Management > Flush Magento Cache)
Configuration settings
In this tab you can setup the SEO Filter URL module with these options:
Enable Search Engine friendly Filter URL: Enable or disable the URL overwrites.

## Specifications
 - Crongroup
	- magetun

 - Cronjob
	- magetun_seofilterurl_cleanerseofilterurl


