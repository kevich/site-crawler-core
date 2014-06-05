<?php

namespace Crawler;

use Mockery\MockInterface;

/**
 * Class Runner
 * @package Crawler
 *
 * Main class for running multi-thread requests to url source
 */
class RunnerJS implements RunnerInterface
{
    /**
     * @var \Crawler\Util\Pipe
     */
    private $pipe;

    /**
     * @var \Crawler\Parser\ParserInterface
     */
    private $parser;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @var \Crawler\Filter\FilterInterface
     */
    private $filter;

    /**
     * @param \Crawler\Parser\ParserInterface $parser
     * @param null|\Monolog\Logger $logger
     * @param array $customConfiguration
     */
    public function __construct($parser, $logger = null, $customConfiguration = array())
    {
        $this->parser = $parser;
        $this->logger = $logger;
        $this->pipe = new Util\Pipe();
    }

    /**
     * @param \Crawler\Filter\FilterInterface
     */
    public function setFilter($filter)
    {
        if ($filter instanceof Filter\FilterInterface) {
            $this->filter = $filter;
        }
    }
    
    /**
     * @param string $url
     * @param \Crawler\ItemSource\ItemSourceInterface $itemSource
     * @return \Crawler\ItemSource\ItemSourceInterface
     */
    public function proceed($url, $itemSource)
    {
        $hash = md5($url . microtime());
        $marker = "={$hash}=";

        $this->log('info', "Processing $url");

        $parserNameStack = explode('\\', get_class($this->parser));
        $parserName = array_pop($parserNameStack);

        $cmd = 'phantomjs ' . __DIR__ . '/../../../../scripts/phantom/' . strtolower($parserName) . '.js ' . escapeshellcmd($url) . ' ' . escapeshellcmd($marker);

        $this->log('debug', "Processing items on cmd {$cmd}");
        $this->pipe->open($cmd);

        while (($body = $this->pipe->readWithMarker($marker, 100))) {
            $source = $body;
            if ($this->filter) {
                $this->log('debug', 'Apply filter: ' . get_class($this->filter));
                $body = $this->filter->apply($body);
            }

            $this->log('info', 'Adding item with size: ' . strlen($body));
            $itemSource->addItem($this->parser->parseItemDetails($body, $source));
        }

        if (!isset($source)) { // watch out for condition
            $this->log('err', 'No response from command in pipe');
        }

        $this->pipe->close();

        $this->log('info', "Finished processing $url");
        return $itemSource;
    }

    /**
     * @param int $number
     */
    public function setParallelRequests($number)
    {
        // Phantom runs in one process for now
    }

    public function setTestPipe($pipe)
    {
        if ($pipe instanceof MockInterface) {
            $this->pipe = $pipe;
        }
    }

//----------------------------------------------------------------------------------------------------------------------

    private function log($level, $text)
    {
        if (!is_null($this->logger)) {
            $this->logger->$level($text);
        }
    }

}