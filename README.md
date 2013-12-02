# Composer installer for eZ Publish Legacy Stack

This installer lets you install extensions for eZ Publish legacy (4.x) with [Composer](http://getcomposer.org).

It also helps you install eZ Publish legacy (4.x) itself, by not deleting your settings and custom code
when you upgrade to a new release.

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

### Extension name vs package name
By default, the legacy extension gets installed in a directory named by your package pretty name. For example, package `lolautruche/sqliimport` gets installed in a directory named `sqliimport`.
If you ever need to name your composer package differently from your extension name (for example, legacy extension ezfind comes in a `ezsystems/ezfind-ls` package), you may tell composer to use a specifica extension name rather than the package's pretty name. Just add an `ezpublish-legacy-extension-name` extra option in your composer.json file :

```json
{
    "name": "ezsystems/ezfind-ls",
    "description": "eZ Find is a search extension for eZ Publish legacy, providing more functionality and better results than the default search in eZ Publish.",
    "type": "ezpublish-legacy-extension",
    "license": "GPL-2.0",
    "authors": [
        {
            "name": "eZ Publish dev-team & eZ Community",
            "homepage": "https://github.com/ezsystems/ezfind/contributors"
        }
    ],
    "minimum-stability": "dev",
    "require": {
        "ezsystems/ezpublish-legacy-installer": "*"
    },
    "extra": {
        "ezpublish-legacy-extension-name": "ezfind"
    }
}

```

## How to install in my project
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
