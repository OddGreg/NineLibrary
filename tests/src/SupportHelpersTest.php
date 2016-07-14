<?php namespace Nine\Library;

use Tests\Ordinary;

/**
 * Test the Collection Class
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes disabled
 */
class SupportHelpersTest extends \PHPUnit_Framework_TestCase
{
    /** @var Lib */
    private $lib;

    public function setUp()
    {
        $this->lib = new Lib;
    }

    public function test_get_class_name()
    {
        static::assertEquals(Ordinary::class, $this->lib->get_class_name(new Ordinary([])));
        static::assertEquals(Ordinary::class, $this->lib->get_class_name(Ordinary::class));
    }

    public function test_value()
    {
        $object = new Ordinary([]);
        $object->{'data'} = 'test';
        $object->{'call_thing'} = function () { return 'Ding!'; };

        $values = [
            'callable'     => function () { return 100; },
            'string'       => 'This is a string',
            'number'       => 1024,
            'float'        => 50.75,
            'object'       => $object,
            'callable_key' => 'I was called',
        ];

        static::assertEquals(100, $this->lib->value($values, 'callable', 'failed'));
        static::assertEquals('This is a string', $this->lib->value($values, 'string', 'failed'));
        static::assertEquals(1024, $this->lib->value($values, 'number', 'failed'));
        static::assertEquals(50.75, $this->lib->value($values, 'float', 'failed'));
        static::assertEquals('test', $this->lib->value($values, 'object.data', 'failed'));
        static::assertEquals('failed', $this->lib->value($values, 'not_valid', 'failed'));
        static::assertEquals('Ding!', $this->lib->value($values, 'object.call_thing', 'failed'));
        static::assertEquals('failed', $this->lib->value(NULL, 'pants', 'failed'));


        static::assertEquals('I was called', $this->lib->value($values,
            function ($array, $default) {
                return $array['callable_key'];
            },
            'failed')
        );

    }

}
