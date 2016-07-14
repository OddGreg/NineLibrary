<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class ArrayAccessible extends \ArrayObject
{
    /**
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        parent::__construct($values);
        $this->values = $values;
    }

    public function toArray()
    {
        return $this->values;
    }

}
