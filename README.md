# Webpack Dev Server Valet Driver

Custom driver for [Laravel Valet](https://laravel.com/docs/master/valet).

Works with Vue, Poi 9, Poi 10 and more!

## What is this?

This is a custom driver to automatically run the server specified by `serve` in your `package.json scripts`.

## Installation

1. Copy the `*.php` files into your `~/.valet/Drivers` directory.
2. Done :)

## FAQ

Q: What if I need to restart the webpack dev server?
A: Append `?restart=1` to the URL and the webpack dev server will be restarted.
