<?php

class Crawler
{
    /**
     * @var array
     */
    private $config = array();

    /**
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->config = $config;
    }
    /**
     * @param $url
     * @param $type
     * @param $runner
     * @param null|\Monolog\Logger $logger
     * @param null $testRunner
     * @return null|\Crawler\ItemSource\ItemSourceInterface
     */
    public function crawlItems($url, $type, $runner, $logger = null, $testRunner = null)
    {
        $parserClassName = '\\Crawler\\Parser\\' . $type;
        if (class_exists($parserClassName)
            && is_subclass_of(
                $parserClassName,
                '\\Crawler\\Parser\\ParserInterface'
            )
        ) {
            /** @var \Crawler\Parser\ParserInterface $parser */
            $parser = new $parserClassName();
            $itemSourceClassName = $parser->getItemSourceClassName();
            if (is_subclass_of(
                $itemSourceClassName,
                '\\Crawler\\ItemSource\\ItemSourceInterface'
            )
            ) {
                $classRunner = "\\Crawler\\{$runner}";
                /* @var $r \Crawler\RunnerInterface */
                $r = !is_null($testRunner) ? $testRunner : new $classRunner($parser, $logger, isset(
                $this->config['runner_config']['defaults']
                ) ? $this->config['runner_config']['defaults'] : array());

                if (isset($this->config['runner_config']['parallelRequests'])) {
                    $r->setParallelRequests($this->config['runner_config']['parallelRequests']);
                }

                if (!empty($this->config['filter'])) {
                    $classFilter = "\\Crawler\\Filter\\{$this->config['filter']}";
                    $r->setFilter(new $classFilter);
                }
                
                return $r->proceed($url, new $itemSourceClassName());
            } else {
                return null;
            }
        } else {
            return null;
        }
    }


} 