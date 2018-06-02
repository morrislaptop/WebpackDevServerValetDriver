<?php

require_once __DIR__ . '/WebpackDevServerBaseDriver.php';

class LaravelMixValetDriver extends WebpackDevServerBaseDriver
{
    /**
     * Use this class to decide whether we should send
     * the request through Laravel or the dev server.
     */
    protected $laravelDriver;

    public function __construct()
    {
        $this->laravelDriver = new LaravelValetDriver();
    }
    
    protected function getRunner() {
        return 'npm run hot -- --port %s';
    }

    protected function getStaticFolder()
    {
        return 'public';
    }

    protected function getDevDependency()
    {
        return 'laravel-mix';
    }

    public function isStaticFile($sitePath, $siteName, $uri)
    {
        return $this->laravelDriver->isStaticFile($sitePath, $siteName, $uri);
    }


    public function frontControllerPath($sitePath, $siteName, $uri) {
        try {
            return parent::frontControllerPath($sitePath, $siteName, $uri);
        }
        catch (Exception $e) {
            return $this->laravelDriver->frontControllerPath($sitePath, $siteName, $uri);
        }
    }
}