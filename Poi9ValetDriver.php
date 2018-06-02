<?php

require_once __DIR__.'/WebpackDevServerBaseDriver.php';

class Poi9ValetDriver extends WebpackDevServerBaseDriver
{
    protected function getRunner()
    {
        return 'npm run dev -- --port %s';
    }

    protected function getStaticFolder()
    {
        return 'static';
    }

    protected function getDevDependency()
    {
        return 'poi';
    }

    protected function getDevDependencyVersionPattern()
    {
        return '/\^9/';
    }

    protected function filterDevContent($content)
    {
        $search = ['/client.js', '/vendor.js', '/manifest.js'];
        $replace = [
            "//{$this->devServerHost}:{$this->port}/client.js",
            "//{$this->devServerHost}:{$this->port}/vendor.js",
            "//{$this->devServerHost}:{$this->port}/manifest.js",
        ];

        return str_replace($search, $replace, $content);
    }
}
