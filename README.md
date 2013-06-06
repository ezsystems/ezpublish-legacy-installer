# Composer installer for eZ Publish legacy extensions

This installer lets you install extensions for eZ Publish legacy (4.x) with [Composer](http://getcomposer.org).

## Installable extensions
To be able to install a legacy extension, it must be properly exposed to Composer with a valid composer.json file
(check [Composer documentation](http://getcomposer.org/doc/) for more information), declaring an `ezpublish-legacy-extension` type.

Example for SQLIImport:

```json
{
    "name": "lolautruche/sqliimport",
    "type": "ezpublish-legacy-extension",
    "description": "Import extension for eZ Publish legacy.",
    "license": "GPL-2.0",
    "minimum-stability": "dev",
    "require": {
        "php": ">=5.3.3",
        "ezsystems/ezpublish-legacy-extension-installer": "*"
    }
}
```

## How to install in my project
All you need to do is create a composer.json at the root of your project and require the extension:

```json
{
    "name": "myvendorname/myproject",
    "description": "My super cool eZ Publish project",
    "license": "GPL-2.0",
    "minimum-stability": "dev",
    "require": {
        "php": ">=5.3.3",
        "lolautruche/sqliimport": "~1.2"
    }
}
```

Then run `php composer.phar install` (assuming you have already properly installed Composer of course :wink:).

### eZ Publish 5 case
By default, the legacy extension installer assumes that eZ Publish legacy is installed in the current folder; in other
words, it is configured for pure-eZ Publish 4 projects.
If this is not the case (like in eZ Publish 5, where it resides in the `ezpublish_legacy/` folder), then you'll need to configure where it is:

```json
{
    "name": "myvendorname/myproject",
    "description": "My super cool eZ Publish 5 project",
    "license": "GPL-2.0",
    "minimum-stability": "dev",
    "require": {
        "php": ">=5.3.3",
        "lolautruche/sqliimport": "~1.2"
    },
    "extra": {
        "ezpublish-legacy-dir": "ezpublish_legacy"
    }
}
```
