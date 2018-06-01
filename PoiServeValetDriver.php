<?php

require_once __DIR__ . '/BaseServeDriver.php';

class PoiServeValetDriver extends BaseServeDriver
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

    protected function filterDevContent($content)
    {
        return str_replace('/app.js', "//{$this->devServerHost}:{$this->port}/app.js", $content);
    }
}