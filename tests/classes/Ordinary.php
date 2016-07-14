<?php namespace Tests;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class Ordinary
{
    /**
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function toArray()
    {
        return $this->values;
    }

}
