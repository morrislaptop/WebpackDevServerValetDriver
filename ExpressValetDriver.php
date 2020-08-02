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
        $filters = "$"."body = str_replace('/socket.io/socket.io.js','http://{$this->devServerHost}:{$this->port}/socket.io/socket.io.js',$"."body);";
        $filters .= "$"."body = str_replace('io()','io(\"http://{$this->devServerHost}:{$this->port}/\")',$"."body);";
        // $filters .= "echo '122';";
        return $filters;
    }
}
