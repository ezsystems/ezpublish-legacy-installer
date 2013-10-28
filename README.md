# Composer installer for eZ Publish legacy

This installer lets you install eZ Publish legacy (4.x) with [Composer](http://getcomposer.org).
Supported package types are:
- eZ Publish legacy (4.x) extensions
- eZ Publish legacy (4.x) itself (useful when installing it as  dependency from within eZ Publish 5.x)
- eZ Publish legacy (4.x) settings

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
        "ezsystems/ezpublish-legacy-installer": "*"
    }
}
```

Note: the "name" of the extension in the composer.json file (the part after the 1st slash character) *must* be the same as
the name of the extension used by ini settings.

## Installable settings
To be able to manage eZ Publish legacy (4.x) settings via Composer, you should create a Composer package with your settings.
In short, the package should contain the following structure:
```
|_ override/*
|
|_ siteaccess/*
|
|_ *.ini (default ezpublish settings)
|
|_ composer.json
```
In composer.json, the package type to use is "ezpublish-legacy-settings".
*TAKE CARE*: any preexisting file in the settings directory will be wiped out by Composer

## How to use in your project
All you need to do is create a composer.json at the root of your project and require the extension
(if the extension is not published on packagist, you also need to tell composer where to find it):

```json
{
    "name": "myvendorname/myproject",
    "description": "My super cool eZ Publish project",
    "license": "GPL-2.0",
    "minimum-stability": "dev",
    "require": {
        "php": ">=5.3.3",
        "lolautruche/sqliimport": "~1.2"
    },
    "repositories" : [
        {
             "type": "vcs",
             "url": "https://github.com/lolautruche/sqliimport.git"
        }
    ]
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
    "repositories" : [
        {
             "type": "vcs",
             "url": "https://github.com/lolautruche/sqliimport.git"
        }
    ],
    "extra": {
        "ezpublish-legacy-dir": "ezpublish_legacy"
    }
}
```
