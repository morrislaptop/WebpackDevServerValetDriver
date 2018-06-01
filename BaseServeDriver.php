<?php

abstract class BaseServeDriver extends ValetDriver
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
     * Interval of seconds to check if the server
     * has booted up.
     */
    protected $sleepInterval = 3;

    /**
     * Max number of intervals to wait for the server
     * to start.
     * 
     * 20 times * 3 seconds = 60 seconds total
     */
    protected $maxIntervals = 3;

    /**
     * Log file for debugging
     */
    protected $log = __DIR__ . '/log';

    /**
     * Log file for output
     */
    protected $out = __DIR__ . '/out';

    /**
     * File path to store site names to pids
     */
    protected $pids = __DIR__ . '/pids.json';

    /**
     * Folder which contains static assets
     */
    abstract protected function getStaticFolder();

    /**
     * Get the command to start the server. Use %s for the port.
     */
    abstract protected function getRunner();

    /**
     * Get the dev dependency to check for
     */
    abstract protected function getDevDependency();

    /**
     * Modify the output from the dev server. Useful for pointing
     * scripts directly to the dev server instead of proxying 
     * through Valet. e.g. WebSockets
     */
    protected function filterDevContent($content) {
        return $content;
    }

    /**
     * Add info to log file.
     */
    protected function log($var) {
        error_log(var_export($var, true) . "\n", 3, $this->log);
    }

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

        if (! $this->isNodeServeSite()) return false;

        if ($this->wantsToRestart($uri)) $this->stopServer();

        if (! $this->isServerRunning()) $this->startServer();

        return true;
    }

    protected function isNodeServeSite()
    {
        $path = $this->sitePath . '/package.json';

        if (!file_exists($path)) return false;

        $package = json_decode(file_get_contents($path));
        $dep = $this->getDevDependency();

        return !empty($package->devDependencies->$dep);
    }

    /**
     * Returns true if a restart is wanted.
     * 
     * Add restart=1 to the URL to return true
     */
    protected function wantsToRestart() {
        return strpos($_SERVER['REQUEST_URI'], 'restart=1') !== false;
    }

    /**
     * Finds the process running for the server and stops it.
     */
    protected function stopServer()
    {
        $this->stopProcess($this->getPid());
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

        // Set PATH manually so it can find node?
        putenv('PATH=/usr/local/bin:/bin');

        $command = sprintf($this->getRunner(), $this->port);
        $append = false;

        $cmd = sprintf('%s %s %s 2>&1 & echo $!', $command, ($append) ? '>>' : '>', $this->out);
        $this->log($cmd);
        $pid = (int) shell_exec($cmd);

        $this->savePid($pid);

        try {
            $this->waitForServerToStart($pid);
        }
        catch (Exception $e) {
            $this->stopProcess($pid);

            throw new Exception(file_get_contents($this->out), 0, $e);
        }
    }

    /**
     * Blocks execution until the server is handling requests.
     * 
     * @todo max wait
     * @todo Check process is running
     */
    protected function waitForServerToStart($pid)
    {
        $count = 0;

        while (!$this->isServerRunning()) {
            $this->throwIfNotRunning($pid);
            if ($count > $this->maxIntervals) throw new Exception('Timed out');

            sleep($this->sleepInterval);
            $count++;
        }
    }

    /**
     * Returns the process id for current siteName
     */
    protected function getPid()
    {
        $data = json_decode(file_get_contents($this->pids), true);

        return $data[$this->siteName];
    }

    /**
     * Saves the process id for current siteName
     */
    protected function savePid($pid)
    {
        $data = [];
        
        if (file_exists($this->pids)) {
            $data = json_decode(file_get_contents($this->pids), true);
        }

        $data[$this->siteName] = $pid;

        file_put_contents($this->pids, json_encode($data));
    }

    /**
     * Checks if the process is still running
     */
    public function throwIfNotRunning($pid)
    {
        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $pid));
            if (count(preg_split("/\n/", $result)) > 2 && !preg_match('/ERROR: Process ID out of range/', $result)) {
                return true;
            }
        } 
        catch (Exception $e) {
        }

        throw new Exception('Not running');
    }

    /**
     * Stops the process.
     *
     * @return bool `true` if the processes was stopped, `false` otherwise.
     */
    public function stopProcess($pid)
    {
        try {
            $result = shell_exec(sprintf('kill %d 2>&1', $pid));
            if (!preg_match('/No such process/', $result)) {
                return true;
            }
        } 
        catch (Exception $e) {
        }

        return false;
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
        $folder = $this->getStaticFolder();

        if (file_exists($staticFilePath = "$sitePath/$folder/$uri")) {
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
        $page = $this->getFromDevServer($uri);

        $content = $this->filterDevContent($page['content']);

        $tmp = tempnam(sys_get_temp_dir(), 'valet');
        file_put_contents($tmp, $content);

        array_map('header', $page['headers']);
        
        return $tmp;
    }

    /**
     * Get response from the dev server.
     */
    protected function getFromDevServer($uri)
    {
        $uri = "http://{$this->devServerHost}:{$this->port}{$uri}";

        $context = stream_context_create(['http' => ['header' => 'Accept: */*']]);
        $content = file_get_contents($uri, false, $context);
        
        return ['content' => $content, 'headers' => $http_response_header];
    }
}