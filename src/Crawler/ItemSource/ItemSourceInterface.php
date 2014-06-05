<?php


namespace Crawler\ItemSource;


interface ItemSourceInterface {

    /**
     * @param \Crawler\ItemSource\FieldsModifierInterface $fieldsModifier
     */
    function __construct($fieldsModifier = null);

    /**
     * @return array
     */
    public function next();

    /**
     * @param array $properties
     * @return void
     */
    public function addItem($properties);

    /**
     * @return string
     */
    public function getItemsToJSON();

    /**
     * @param string $s
     * @return \Crawler\ItemSource\ItemSourceInterface
     */
    public function importFromJSON($s);

    /**
     * @param string $xslString
     * @param callable|null $filterFunction
     * @return string
     */
    public function getItemsToXML($xslString, $filterFunction = null);
} 