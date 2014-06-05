<?php

namespace Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Event\CompleteEvent;
use Mockery\MockInterface;

/**
 * Class Runner
 * @package Crawler
 *
 * Main class for running multi-thread requests to url source
 */
class RunnerCurl implements RunnerInterface
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

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
     * @var int
     */
    private $parallelRequests = 25;

    /**
     * @param \Crawler\Parser\ParserInterface $parser
     * @param null|\Monolog\Logger $logger
     * @param array $customConfiguration
     */
    public function __construct($parser, $logger = null, $customConfiguration = array())
    {
        $this->parser = $parser;
        $this->logger = $logger;
        $this->client = new Client(
            array(
                'defaults' => array_merge_recursive(
                    array(
                        'verify' => false,
                        'headers' => array(
                            'Accept-Encoding' => 'gzip,deflate',
                        ),
                        'cookies' => true,
                    ),
                    $customConfiguration
                )
            )
        );
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
     * @param int $number
     */
    public function setParallelRequests($number)
    {
        $this->parallelRequests = (int) $number;
        $this->log('debug', "Setting parallelRequests config value to {$this->parallelRequests}");
    }

    /**
     * @param $url string
     * @param $itemSource \Crawler\ItemSource\ItemSourceInterface
     * @return \Crawler\ItemSource\ItemSourceInterface
     */
    public function proceed($url, $itemSource)
    {
        $this->log('info', "Processing $url");
        try {
            $response = $this->client->get($url);

            $this->log('debug', "Getting pages urls");
            $pagesUrls = $this->parser->getPagesUrls($response->getBody(), $url);

            if (is_null($pagesUrls)) {
                $this->log('debug', "Got one page, using existing one");
                $this->processPageResponse($response, $itemSource);
            } else {
                $this->log('debug', "Fetching pages from urls");
                $this->proceedMultiUrls(
                    $pagesUrls,
                    function (CompleteEvent $event) use ($itemSource) {
                        $this->processPageResponse($event->getResponse(), $itemSource);
                    }
                );
            }
        } catch (\Exception $exception) {
            $this->log('err', $exception->getMessage());
        }

        $this->log('info', "Finished processing $url");

        return $itemSource;
    }

    public function setTestClient($client)
    {
        if ($client instanceof MockInterface) {
            $this->client = $client;
        }
    }

//----------------------------------------------------------------------------------------------------------------------

    /**
     * @param \GuzzleHttp\Message\ResponseInterface $response
     * @param \Crawler\ItemSource\ItemSourceInterface $itemSource
     */
    private function processPageResponse($response, $itemSource)
    {
        $this->log('debug', "Processing page response");
        $itemsUrls = $this->parser->getItemsUrls(
            $response->getBody(),
            $response->getEffectiveUrl()
        );
        $isSimpleUrls = is_array($itemsUrls) && !is_array(current($itemsUrls));
        $this->proceedMultiUrls(
            $isSimpleUrls ? $itemsUrls : array_keys($itemsUrls),
            function (CompleteEvent $event) use ($isSimpleUrls, $itemsUrls, $itemSource) {
                $sourceUrl = $event->getResponse()->getEffectiveUrl();

                $body = $event->getResponse()->getBody();
                if ($this->filter) {
                    $this->log('debug', 'Apply filter: ' . get_class($this->filter));
                    $body = $this->filter->apply($body);
                }

                $this->log('info', "Adding item with url: {$sourceUrl}");

                $source = $isSimpleUrls ? $sourceUrl : array($sourceUrl => $itemsUrls[$sourceUrl]);
                $itemSource->addItem($this->parser->parseItemDetails($body, $source));
            }
        );
    }

    /**
     * @param $urlsToProceed
     * @param $callback
     * @return array|\GuzzleHttp\Message\Response|null
     */
    private function proceedMultiUrls($urlsToProceed, $callback)
    {
        $this->log('debug', "Got multi urls to proceed: " . print_r($urlsToProceed, true));
        $responses = null;
        if ($urlsToProceed && count($urlsToProceed) > 0) {
            try {
                $this->client->sendAll($this->arrayOfRequests($urlsToProceed),
                    array(
                        'complete' => $callback,
                        'parallel' => $this->parallelRequests
                    ));
            } catch (\Exception $e) {

                $this->log('err', "The following exceptions were encountered:");
                /** @var $exception \Exception */
                foreach ($e as $exception) {
                    $this->log('err', $exception->getMessage());
                }

            }
        }
        return $responses;
    }

    /**
     * @param $urls array
     * @return array
     */
    private function arrayOfRequests($urls)
    {
        $arrayOfRequests = array();
        foreach ($urls as $url) {
            $arrayOfRequests[] = $this->client->createRequest('GET', $url);
        }
        return $arrayOfRequests;
    }

    private function log($level, $text)
    {
        if (!is_null($this->logger)) {
            $this->logger->$level($text);
        }
    }

}