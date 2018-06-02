<?php

require_once __DIR__ . '/WebpackDevServerBaseDriver.php';

class VueCliValetDriver extends WebpackDevServerBaseDriver
{
    protected function getRunner() {
        return 'yarn serve --port %s';
    }

    protected function getStaticFolder()
    {
        return 'public';
    }

    protected function getDevDependency()
    {
        return '@vue/cli-service';
    }

    protected function filterDevContent($content)
    {
        return str_replace('/app.js', "//{$this->devServerHost}:{$this->port}/app.js", $content);
    }
}