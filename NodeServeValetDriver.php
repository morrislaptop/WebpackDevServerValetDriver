<?php

class ServeValetDriver extends ValetDriver
{
    protected $sitePath;
    protected $siteName;
    protected $port;

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

        if (! $this->isVueSite()) return false;

        if (! $this->isServerRunning()) $this->startServer();

        return true;
    }

    protected function isVueSite()
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
     * 
     * @todo Wait until app starts serving...
     */
    protected function startServer()
    {
        chdir($this->sitePath);

        putenv('PATH=/usr/local/bin');
        $process = new BackgroundProcess("yarn serve --port 1036 --public http://localhost:1036 --publicPath http://localhost:1036 --host 0.0.0.0");

        $process->run(__DIR__ . '/out', true);

        sleep(5);
    }

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
        $uri = "http://localhost:$this->port$uri";
        $page = file_get_contents($uri);

        $page = str_replace('/app.js', '//localhost:1036/app.js', $page);

        $tmp = tempnam(sys_get_temp_dir(), 'valet');
        file_put_contents($tmp, $page);
        
        return $tmp;
    }
}

/**
 * This file is part of cocur/background-process.
 *
 * (c) 2013-2015 Florian Eckerstorfer
 */

/**
 * BackgroundProcess.
 *
 * Runs a process in the background.
 *
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2013-2015 Florian Eckerstorfer
 * @license   http://opensource.org/licenses/MIT The MIT License
 * @link      https://florian.ec/articles/running-background-processes-in-php/ Running background processes in PHP
 */
class BackgroundProcess
{
    const OS_WINDOWS = 1;
    const OS_NIX = 2;
    const OS_OTHER = 3;

    /**
     * @var string
     */
    private $command;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var int
     */
    protected $serverOS;

    /**
     * @param string $command The command to execute
     *
     * @codeCoverageIgnore
     */
    public function __construct($command = null)
    {
        $this->command = $command;
        $this->serverOS = $this->getOS();
    }

    /**
     * Runs the command in a background process.
     *
     * @param string $outputFile File to write the output of the process to; defaults to /dev/null
     *                           currently $outputFile has no effect when used in conjunction with a Windows server
     * @param bool $append - set to true if output should be appended to $outputfile
     */
    public function run($outputFile = '/dev/null', $append = false)
    {
        if ($this->command === null) {
            return;
        }

        switch ($this->getOS()) {
            case self::OS_WINDOWS:
                shell_exec(sprintf('%s &', $this->command, $outputFile));
                break;
            case self::OS_NIX:
                $this->pid = (int)shell_exec(sprintf('%s %s %s 2>&1 & echo $!', $this->command, ($append) ? '>>' : '>', $outputFile));
                break;
            default:
                throw new RuntimeException(sprintf(
                    'Could not execute command "%s" because operating system "%s" is not supported by ' .
                        'Cocur\BackgroundProcess.',
                    $this->command,
                    PHP_OS
                ));
        }
    }

    /**
     * Returns if the process is currently running.
     *
     * @return bool TRUE if the process is running, FALSE if not.
     */
    public function isRunning()
    {
        $this->checkSupportingOS('Cocur\BackgroundProcess can only check if a process is running on *nix-based ' .
            'systems, such as Unix, Linux or Mac OS X. You are running "%s".');

        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $this->pid));
            if (count(preg_split("/\n/", $result)) > 2 && !preg_match('/ERROR: Process ID out of range/', $result)) {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * Stops the process.
     *
     * @return bool `true` if the processes was stopped, `false` otherwise.
     */
    public function stop()
    {
        $this->checkSupportingOS('Cocur\BackgroundProcess can only stop a process on *nix-based systems, such as ' .
            'Unix, Linux or Mac OS X. You are running "%s".');

        try {
            $result = shell_exec(sprintf('kill %d 2>&1', $this->pid));
            if (!preg_match('/No such process/', $result)) {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * Returns the ID of the process.
     *
     * @return int The ID of the process
     */
    public function getPid()
    {
        $this->checkSupportingOS('Cocur\BackgroundProcess can only return the PID of a process on *nix-based systems, ' .
            'such as Unix, Linux or Mac OS X. You are running "%s".');

        return $this->pid;
    }

    /**
     * Set the process id.
     *
     * @param $pid
     */
    protected function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    protected function getOS()
    {
        $os = strtoupper(PHP_OS);

        if (substr($os, 0, 3) === 'WIN') {
            return self::OS_WINDOWS;
        } else if ($os === 'LINUX' || $os === 'FREEBSD' || $os === 'DARWIN') {
            return self::OS_NIX;
        }

        return self::OS_OTHER;
    }

    /**
     * @param string $message Exception message if the OS is not supported
     *
     * @throws RuntimeException if the operating system is not supported by Cocur\BackgroundProcess
     *
     * @codeCoverageIgnore
     */
    protected function checkSupportingOS($message)
    {
        if ($this->getOS() !== self::OS_NIX) {
            throw new RuntimeException(sprintf($message, PHP_OS));
        }
    }

    /**
     * @param int $pid PID of process to resume
     *
     * @return Cocur\BackgroundProcess\BackgroundProcess
     */
    static public function createFromPID($pid)
    {
        $process = new self();
        $process->setPid($pid);

        return $process;
    }
}