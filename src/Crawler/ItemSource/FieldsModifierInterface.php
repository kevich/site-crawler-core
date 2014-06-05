<?php


namespace Crawler\ItemSource;


interface FieldsModifierInterface {

    /**
     * @param array $item
     * @return array
     */
    public function prepareItem($item);

} 