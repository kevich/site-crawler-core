<?php

namespace Crawler\Util;

class Pipe
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var resource
     */
    private $proc;

    /**
     * @var array
     */
    private $pipes;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var integer Timestamp in seconds after that process should be considered hanging
     */
    private $timeEnd;

    /**
     * @var integer Amount of seconds $timeEnd is changed with
     */
    private $timeSpan;


    /**
     * @param $command
     * @param integer $timeSpan fraction of change for timeout
     * @return bool
     */
    public function open($command, $timeSpan = 600)
    {
        $this->command = $command;
        $this->timeSpan = $timeSpan;
        $this->buffer = '';

        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin
            1 => array("pipe", "w"), // stdout
        );

        $this->proc = proc_open($this->command, $descriptorspec, $pipes);

        if (is_resource($this->proc)) {
            $this->pipes = $pipes;
            stream_set_blocking($this->pipes[1], 0); // Do not hang on read
            return true;
        }

        return false;
    }

    /**
     * @param integer $bufflen
     * @return string
     */
    public function read($bufflen = 1000)
    {
        $s = $this->streamGetContents($bufflen);
        
        $this->buffer .= $s;
        return $s;
    }

    /**
     *
     * @param string $marker
     * @param integer $bufflen
     * @return string|false
     */
    public function readWithMarker($marker, $bufflen = 1000)
    {
        do {
            $s = $this->read($bufflen);
            $pos = strpos($this->buffer, $marker);
            if ($pos !== false) {
                $item = substr($this->buffer, 0, $pos);
                $this->buffer = substr($this->buffer, $pos + strlen($marker));
                return $item;
            }

            $status = $this->getStatusRunning();
        } while ($s || $status);

        return false;
    }
    
    /**
     * @return void
     */
    public function getStatusRunning()
    {
        if (!is_resource($this->proc)) {
            return false;
        }
        $status = proc_get_status($this->proc);
        return isset($status['running']) && $status['running'] && !$this->isTimeout();
    }    

    /**
     * @return void
     */
    public function close()
    {
        if (!is_resource($this->proc)) {
            return;
        }

        $status = proc_get_status($this->proc);
        if (true == $status['running']) {
            // Closing pipes like for proc_close()
            foreach ($this->pipes as $pipe) {
                fclose($pipe);
            }
            // But, don't wait
            proc_terminate($this->proc);
        }
    }

    /**
     * @param integer $bufflen
     * @return string
     */
    public function streamGetContents($bufflen = 1000)
    {
        if (!is_resource($this->proc)) {
            return '';
        }
        $this->setupTimeout();
        $dataFromStream =  stream_get_contents($this->pipes[1], $bufflen);
        if (strlen($dataFromStream)) {
            $this->pushTimeout();
        }
        return $dataFromStream;
    }

    /**
     * Push border of timeout to future
     */
    protected function pushTimeout()
    {
        $this->timeEnd = time() + $this->timeSpan;
    }

    /**
     * Setup border of timeout for the first time
     */
    protected function setupTimeout()
    {
        if (!$this->timeEnd) {
            $this->pushTimeout();
        }
    }

    /**
     * Check if timeout border is passed
     * @return bool
     */
    protected function isTimeout()
    {
        return $this->timeEnd && (time() > $this->timeEnd);
    }
}
