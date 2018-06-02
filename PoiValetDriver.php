<?php

require_once __DIR__ . '/WebpackDevServerBaseDriver.php';

class PoiValetDriver extends WebpackDevServerBaseDriver
{
    protected function getRunner() {
        return 'yarn dev --port %s';
    }

    protected function getStaticFolder()
    {
        return 'static';
    }

    protected function getDevDependency()
    {
        return 'poi';
    }

    protected function getDevDependencyVersionPattern() {
        return '/\^10/';
      }

    protected function filterDevContent($content)
    {
        $search = ['/vendors~main.js', '/main.js'];
        $replace = [
            "//{$this->devServerHost}:{$this->port}/vendors~main.js",
            "//{$this->devServerHost}:{$this->port}/main.js",
        ];

        return str_replace($search, $replace, $content);
    }
}