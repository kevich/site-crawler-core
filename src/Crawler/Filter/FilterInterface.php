<?php


namespace Crawler\Filter;


interface FilterInterface {

    /**
     * @param string $content
     * @return string
     */
    public function apply($content);
} 