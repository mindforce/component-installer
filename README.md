# Component Installer for [Composer](http://getcomposer.org) into CakePHP projects

Allows installation of Components via [Composer](http://getcomposer.org) into CakePHP projects.

## Install

```
composer require mindforce/component-installer
```

``` json
{
    "require": {
        "mindforce/component-installer": "*"
    }
}
```

## Usage

To install a Component with Composer, add the Component to your *composer.json*
`require` key. The following will install and try to separate assets to right places
for [jQuery](http://jquery.com):

```
composer require components/jquery
```

``` json
{
    "require": {
        "components/jquery": "2.*",
        "mindforce/component-installer": "*"
    }
}
```
