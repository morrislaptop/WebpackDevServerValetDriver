<?php

class NpmRunServeValetDriver extends ValetDriver
{
    /**
     * Holds the full path to the site
     */
    protected $sitePath;

    /**
     * Holds the domain name
     */
    protected $siteName;

    /**
     * Holds the port associated to $siteName
     */
    protected $port;

    /**
     * Holds the URL to the dev server in background
     */
    protected $devServerHost = '127.0.0.1';

    /**
     * The script used to start the server
     */
    protected $runner = 'yarn serve --port %s';

    /**
     * Interval of seconds to check if the server
     * has booted up.
     */
    protected $sleep = 3;

    /**
     * Output to local debug file or /dev/null
     */
    protected $debug = false;

    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        $this->sitePath = $sitePath;
        $this->siteName = $siteName;
        $this->port = $this->getPort();

        if (! $this->isNpmRunServeSite()) return false;

        if (! $this->isServerRunning()) $this->startServer();

        return true;
    }

    protected function isNpmRunServeSite()
    {
        $path = $this->sitePath . '/package.json';

        if (!file_exists($path)) return false;

        $package = json_decode(file_get_contents($path));

        return !empty($package->scripts->serve);
    }

    /**
     * Returns a port number based on the siteName
     */
    protected function getPort()
    {
        return crc32($this->siteName) % 1000 + 1000;
    }

    /**
     * Starts the node server by running the serve
     * command.
     */
    protected function startServer()
    {
        chdir($this->sitePath);

        putenv('PATH=/usr/local/bin');

        $command = sprintf($this->runner, $this->port);
        $append = false;
        $outputFile = $this->debug ? __DIR__ . '/debug' : '/dev/null';

        $cmd = sprintf('%s %s %s 2>&1 & echo $!', $command, ($append) ? '>>' : '>', $outputFile);
        // var_dump($cmd); exit;
        shell_exec($cmd);

        $this->waitForServerToStart();
    }

    /**
     * Blocks execution until the server is handling requests.
     */
    protected function waitForServerToStart()
    {
        while (!$this->isServerRunning()) {
            sleep($this->sleep);
        }
    }

    /**
     * Returns true if the server is already running.
     */
    protected function isServerRunning()
    {
        $fp = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.1);

        if (!$fp) return false;

        fclose($fp);
        
        return true;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($staticFilePath = $sitePath . '/public' . $uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        $uri = "http://{$this->devServerHost}:{$this->port}{$uri}";
        $page = file_get_contents($uri);

        $page = str_replace('/app.js', "//{$this->devServerHost}:{$this->port}/app.js", $page);

        $tmp = tempnam(sys_get_temp_dir(), 'valet');
        file_put_contents($tmp, $page);
        
        return $tmp;
    }
}