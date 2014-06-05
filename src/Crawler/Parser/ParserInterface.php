<?php


namespace Crawler\Parser;


interface ParserInterface {

    /**
     * Should detect if there is any paging on page
     * Returns array of urls of pages with item lists to be processed
     * Should return null if there is no paging, and there is only one page
     *
     * @param string $body
     * @param string $url
     * @return null|array of urls
     */
    public function getPagesUrls($body, $url);

    /**
     * Should parse items urls on page
     * Returns array of urls of pages with item details
     *
     * @param string $body
     * @param string $url
     * @return array of urls
     */
    public function getItemsUrls($body, $url);

    /**
     * Should parse item details
     * Returns item details array
     *
     * @param string $body
     * @param string|array $source
     * @return array of item properties
     */
    public function parseItemDetails($body, $source);

    /**
     * Must return name of ItemSource class used to store data
     *
     * @return string
     */
    public function getItemSourceClassName();
}