<?php
namespace Crawler;

/**
 * Interface RunnerInterface
 * @package Crawler
 */
interface RunnerInterface
{
    /**
     * @param \Crawler\Parser\ParserInterface $parser
     * @param null|\Monolog\Logger $logger
     * @param array $customConfiguration
     */
    public function __construct($parser, $logger = null, $customConfiguration = array());

    /**
     * @param string $url
     * @param \Crawler\ItemSource\ItemSourceInterface $itemSource
     * @return \Crawler\ItemSource\ItemSourceInterface
     */
    public function proceed($url, $itemSource);

    /**
     * @param \Crawler\Filter\FilterInterface $filter
     */
    public function setFilter($filter);

    /**
     * @param int $number
     */
    public function setParallelRequests($number);
}