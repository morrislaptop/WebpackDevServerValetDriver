<?php

require_once __DIR__ . '/WebpackDevServerBaseDriver.php';

class ExpressValetDriver extends WebpackDevServerBaseDriver
{
    protected function getRunner()
    {
        return 'PORT=%s npm start';
    }

    protected function getStaticFolder()
    {
        return 'public';
    }

    protected function getDevDependency()
    {
        return 'express';
    }

    protected function filterDevContent($content)
    {
        return $content;
    }
}
