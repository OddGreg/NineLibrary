<?php namespace Nine\Library;

/**
 * @package Nine
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use ArrayAccessible;
use Tests\Ordinary;

/**
 * Test the framework support functions
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes disabled
 */
class ArrayHelpersTest extends \PHPUnit_Framework_TestCase
{
    public $source_array = [
        'Apples' => 'One',
        'Beets'  => 2,
        'Candy'  => [
            'start' => 'now',
            'end'   => 'then',
        ],
    ];

    public $source_array_table = [
        'Name',
    ];

    /** @var Lib */
    private $lib;

    public function setUp()
    {
        $this->lib = new Lib();
    }

    public function test_array_accessible_to_array_has()
    {
        ### array_accessible

        static::assertTrue($this->lib->array_accessible([]));
        static::assertTrue($this->lib->array_accessible(new ArrayAccessible([])));
        static::assertFalse($this->lib->array_accessible($this->lib));

        ### array_collapse

        $array = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ];

        $expected = [
            1, 2, 3,
            4, 5, 6,
            7, 8, 9,
        ];

        static::assertEquals($expected, $this->lib->array_collapse($array));

        ### array_except($array, $keys)

        static::assertEquals(
            [
                'Apples' => 'One',
                'Candy'  => ['start' => 'now', 'end' => 'then'],
            ],
            $this->lib->array_except($this->source_array, ['Beets'])
        );

        ### array_expand()

        $array = ['a', 'b', 'c', 'd', 'e'];

        $expected = [
            'a' => 'default',
            'b' => 'default',
            'c' => 'default',
            'd' => 'default',
            'e' => 'default',
        ];

        static::assertEquals($expected, $this->lib->array_expand($array, 'default'));

        ### array_fetch

        // given this crazy structure:

        $array = [
            [
                'one' => [
                    'Sam',
                    'Carrie',
                    'Buford',
                ],
                'two' => 'All alone.',
            ],
            [
                'three' => '1 + 2',
                'four'  => '2 + 2',
            ],
            [
                'five' => [
                    'First' => [
                        'Bennie'    => 100,
                        'Crawford'  => 200,
                        'Lightfoot' => 300,
                    ],
                    'Second',
                    'Third',
                ],
                'six'  => 6,
            ],
        ];

        // 'First' is not a first-order index so an empty array is returned
        static::assertEmpty($this->lib->array_fetch($array, 'First'));

        // 'Three' is a first-order index, so it's content array is returned
        static::assertEquals(['1 + 2'], $this->lib->array_fetch($array, 'three'));

        // this should only return the 'five' array structure because 'six' is
        // a sibling and not a child.
        static::assertEquals(
            [ # result array
              [ # 'five' content array
                'First' => [
                    'Bennie'    => 100,
                    'Crawford'  => 200,
                    'Lightfoot' => 300,
                ],
                'Second',
                'Third',
              ],
            ],
            $this->lib->array_fetch($array, 'five.six')
        );

        // this will return a result that follows the nodes to
        // the result: Crawford
        static::assertEquals(
            [
                [ # 'five' segment
                  'First' => [
                      'Bennie'    => 100,
                      'Crawford'  => 200,
                      'Lightfoot' => 300,
                  ],
                  'Second',
                  'Third',
                ],
                [ # the 'First' segment
                  'Bennie'    => 100,
                  'Crawford'  => 200,
                  'Lightfoot' => 300,
                ],
                # the final result segment: Crawford = 200
                200,
            ],
            $this->lib->array_fetch($array, 'five.First.Crawford')
        );

        // not found results in an empty return array
        static::assertEquals([], $this->lib->array_fetch($array, 'totally-not-an-index'));

        // the fetch cascade stops at the first non-retrievable node.
        static::assertEquals([6], $this->lib->array_fetch($array, 'six.totally-not-an-index'));

        ### array_first_match

        $array = [
            'a' => [1, 2],
            'b' => [3, 4],
        ];

        /** @noinspection PhpUnusedParameterInspection */
        static::assertEquals([3, 4], $this->lib->array_first_match(
            $array,
            function ($key, $value) { return array_sum($value) === 7; })
        );

        /** @noinspection PhpUnusedParameterInspection */
        static::assertEquals(['nada'], $this->lib->array_first_match(
            $array,
            function ($key, $value) { return array_sum($value) === 100; },
            # failure to match returns this result
            ['nada'])
        );

        ### array_get

        $array = [
            'book'  => [
                'title'  => 'Book Title',
                'author' => 'Murray McDouche',
            ],
            'movie' => [
                'title'  => 'A Simple Desultory Philippic',
                'author' => 'M. Night Shamalamalambonie',
            ],
        ];

        // exists
        static::assertEquals('A Simple Desultory Philippic', $this->lib->array_get($array, 'movie.title'));
        // default doesn't exist
        static::assertEquals(NULL, $this->lib->array_get($array, 'movie.rating'));
        // doesn't exist - with default return
        static::assertEquals('not found', $this->lib->array_get($array, 'movie.rating', 'not found'));
        // a NULL key simply returns the entire array
        static::assertEquals($array, $this->lib->array_get($array, NULL, 'not found'));

        ### array_has

        $array = [
            'one'   => 1,
            'two'   => 2,
            'three' => [
                'four' => 4,
                'five' => 5,
                'six'  => [
                    'seven' => 7,
                    'eight' => 8,
                ],
            ],
        ];

        static::assertTrue($this->lib->array_has($array, 'three'));
        static::assertTrue($this->lib->array_has($array, 'three.six.eight'));
        static::assertFalse($this->lib->array_has($array, 'two.six.eight'));
        static::assertFalse($this->lib->array_has($array, NULL));
        static::assertFalse($this->lib->array_has($array, 0));

    }

    public function test_array_build__transform()
    {
        ### array_build

        $array = [ # [0..8]
                   ['count' => 1], ['count' => 2], ['count' => 3],
                   ['count' => 4], ['count' => 5], ['count' => 6],
                   ['count' => 7], ['count' => 8], ['count' => 9],
        ];

        $expected = [ # [4..8]
                      4 => ['count' => 5],
                      5 => ['count' => 6],
                      6 => ['count' => 7],
                      7 => ['count' => 8],
                      8 => ['count' => 9],
        ];

        $new_array = $this->lib->array_build($array,
            function ($index, $record) {
                if ($record['count'] > 4) {
                    return [$index, $record];
                }
            }
        );

        static::assertEquals($expected, $new_array);

        ### array_transform_with_callback

        $array = [
            'one' => 1,
            'two' => 2,
        ];

        $expected = [
            'one' => 2,
            'two' => 4,
        ];

        $new_array = $this->lib->array_transform_with_callback($array,
            function ($key, $value) {
                return [$key, $value * 2];
            }
        );

        static::assertEquals($expected, $new_array);

    }

    public function test_array_flatten_to_()
    {
        # array_flatten - array to flattened dictionary

        static::assertEquals(
            [
                0 => 'One',
                1 => 2,
                2 => 'now',
                3 => 'then',
            ],
            $this->lib->array_flatten($this->source_array)
        );

        ### array_forget(&$array, $keys)

        # forget by single key
        $worker = $this->source_array;
        $this->lib->array_forget($worker, 'Candy');
        static::assertEquals(
            [
                'Apples' => 'One',
                'Beets'  => 2,
            ],
            $worker
        );
        # forget by dot path
        $worker = $this->source_array;
        $this->lib->array_forget($worker, 'Candy.start');
        static::assertEquals(
            [
                'Apples' => 'One',
                'Beets'  => 2,
                'Candy'  => ['end' => 'then'],
            ],
            $worker
        );

        ### array_from_string

        static::assertEquals(
            [
                'apples',
                'beets',
                'candy.start',
                'candy.end',
            ],
            $this->lib->array_from_string('apples beets candy.start candy.end')
        );

        ### assoc_from_string

        static::assertEquals(
            [
                'apples'      => 10,
                'beets'       => 'nope',
                'candy.start' => 'now',
                'candy.end'   => 'never',
            ],
            $this->lib->assoc_from_string('apples:10, beets:nope, candy.start:now, candy.end:never')
        );

    }

    public function test_array_get_and_search()
    {
        $searchRA = [
            'name'   => 'greg',
            'record' => [
                'age'    => 100,
                'amount' => 26.58,
                'source' => 'pension',
            ],
        ];

        static::assertEquals('not found', $this->lib->array_get($searchRA, 'record.lazy', 'not found'));
        static::assertEquals($searchRA['record'], $this->lib->array_search_and_replace($searchRA, 'record.lazy', 'not found'));
        static::assertEquals(26.58, $this->lib->array_get($searchRA, 'record.amount', 'not found'));
        //static::assertEquals('not found', $this->lib->search('not.there', $searchRA, 'not found'));

        //ddump([$resultSearch, $resultGet]);
    }

    public function test_array_index_to_array_last()
    {
        ### array_index

        $array = [
            ['id' => '123', 'data' => 'abc'],
            ['id' => '456', 'data' => 'def'],
        ];

        static::assertEquals(
            [
                'abc' => ['id' => '123', 'data' => 'abc'],
                'def' => ['id' => '456', 'data' => 'def'],
            ],
            $this->lib->array_index($array, 'data')
        );

        // returns an empty array is the key does not exist
        static::assertEquals([], $this->lib->array_index($array, 'not-an-index'));

        ### array_last

        $array = [
            'Apples'   => 1,
            'Beets'    => 2,
            'Candy'    => 3,
            'Beef'     => 5,
            'Tomatoes' => 2,
        ];

        /** @noinspection PhpUnusedParameterInspection */
        static::assertEquals(3, $this->lib->array_last($array, function ($key, $value) { return $value * $value === 9; }));
        /** @noinspection PhpUnusedParameterInspection */
        static::assertEquals(NULL, $this->lib->array_last($array, function ($key, $value) { return $value * $value === 100; }));

    }

    public function test_array_insert_before()
    {

        /** @array_insert_before */

        static::assertEquals(
            [
                'Apples' => 'One',
                'Beets'  => 2,
                'Beef'   => ['hamburger', 'roast beef'],
                'Candy'  => ['start' => 'now', 'end' => 'then'],
            ],
            $this->lib->insert_before('Candy', $this->source_array, 'Beef', ['hamburger', 'roast beef'])
        );

        ### array_pull(&$array, $key, $default = NULL)

        # copy the test array
        $worker = $this->source_array;
        # pull 'Beets' -> 2
        static::assertEquals(2, $this->lib->array_pull($worker, 'Beets', $default = FALSE));
        # verify removed from original
        static::assertArrayNotHasKey('Beets', $worker);

    }

    public function test_array_or_object_query()
    {
        ### array_query

        $array = [
            'Apples' => 'One',
            'Beets'  => 2,
            'Beef'   => ['hamburger' => 5.00, 'roast beef' => 9.75],
            'Candy'  => ['start' => 'now', 'end' => 'then'],
        ];

        // exists
        static::assertEquals('now', $this->lib->array_query($array, 'Candy.start'));

        // doesn't exist
        static::assertNull($this->lib->array_query($array, 'Beef.ham'));

        // doesn't exist - with default return value
        static::assertEquals('not there', $this->lib->array_query($array, 'Beef.ham', 'not there'));

        // key with whitespace
        static::assertEquals(9.75, $this->lib->array_query($array, 'Beef.roast beef'));

        // callable returns example sum as a process
        static::assertEquals(['sum' => 14.75], $this->lib->array_query($array, function ($array) {
            return ['sum' => $array['Beef']['hamburger'] + $array['Beef']['roast beef']];
        }));

        $object = $this->lib->cast_array_to_object([
            'user' => [
                'name'  => 'Alphonso',
                'email' => 'alpha@onso.it',
            ],
        ]);

        // verify that the object was created properly
        static::assertObjectHasAttribute('user', $object);

        // property exists
        static::assertEquals('Alphonso', $this->lib->array_query($object, 'user.name'));

        // property does not exist
        static::assertNull($this->lib->array_query($object, 'bad_key'));
        // property does not exists - with default return
        static::assertNull($this->lib->array_query($object, 'bad_key', 'not found'));

        $this->expectException(\InvalidArgumentException::class);

        // property cannot be accessed because the target is neither an array or an object - no default
        $this->lib->array_query('not an array or object', 'bad_key');
    }

    public function test_array_search_and_replace()
    {
        $array = [
            'one'   => 1,
            'two'   => 2,
            'three' => [
                'four' => 4,
                'five' => 5,
                'six'  => [
                    'seven' => 7,
                    'eight' => 8,
                ],
            ],
        ];

        // the current value of 'seven' should be 7.
        static::assertEquals(7, $this->lib->array_query($array, 'three.six.seven'));

        // change an element by replacing the tree
        $this->lib->array_search_and_replace($array, ['three' => ['six' => ['seven' => 10]]]);

        // the new value of 'seven' should be 10.
        static::assertEquals(10, $this->lib->array_query($array, 'three.six.seven'));

        // add a new element
        $this->lib->array_search_and_replace($array, ['three' => ['six' => ['nine' => 9]]]);

        // new element should exist and be 9
        static::assertEquals(9, $this->lib->array_query($array, 'three.six.nine'));
    }

    public function test_array_set()
    {
        $array = [
            'one'   => 1,
            'two'   => 2,
            'three' => [
                'four' => 4,
                'five' => 5,
                'six'  => [
                    'seven' => 7,
                    'eight' => 8,
                ],
            ],
        ];

        // successfully add a new element
        static::assertEquals(
            [
                'seven' => 7,
                'eight' => 8,
                'nine'  => 9,
            ],
            $this->lib->array_set($array, 'three.six.nine', 9)
        );

        // using the '*' wildcard, the array should be overwritten with the passed value
        // default values are coerced into array results when used with '*' or NULL keys.
        static::assertEquals([9], $this->lib->array_set($array, '*', 9));

        $array = [
            'one'   => 1,
            'two'   => 2,
            'three' => [
                'four' => 4,
                'five' => 5,
                'six'  => [
                    'seven' => 7,
                    'eight' => 8,
                ],
            ],
        ];

        $expected = [
            'one'   => 1,
            'two'   => 2,
            'three' => [
                'four'   => 4,
                'five'   => 5,
                'six'    => [
                    'seven' => 7,
                    'eight' => 8,
                ],
                'twenty' => [
                    'one' => 21,
                ],
            ],
        ];

        // create a new node tree (twenty and twenty.one do not exist)
        $this->lib->array_set($array, 'three.twenty.one', 21);
        static::assertEquals($expected, $array);
    }

    public function test_array_sets()
    {
        ### array_subset

        $array = [
            'one'    => 1,
            'two'    => 2,
            'three'  => [
                'four' => 4,
                'five' => 5,
                'six'  => [
                    'seven' => 7,
                    'eight' => 8,
                ],
            ],
            'twenty' => [
                'one' => 21,
            ],
        ];

        // key 'ten' does not exist, so ignore it
        static::assertEquals(
            [
                'twenty' => [
                    'one' => 21,
                ],
            ],
            $this->lib->array_subset($array, ['ten', 'twenty'])
        );
    }

    public function test_array_value_to_array_where()
    {
        $array = [
            'item'      => function () {
                return 100;
            },
            'count'     => 25,
            'remainder' => 60,
        ];

        static::assertEquals(100, $this->lib->array_value($array, 'item'));
        static::assertEquals('not found', $this->lib->array_value($array, 'not-real-key', 'not found'));
        static::assertNull($this->lib->array_value($array, 'not-real-key'));

        $actual = $this->lib->array_where(
            $array,
            function ($key, $value) {
                return value($value) > 50;
            }
        );

        static::assertCount(2, $actual);
        static::assertArrayHasKey('item', $actual);
        static::assertInstanceOf(\Closure::class, $actual['item']);
        static::assertArrayHasKey('remainder', $actual);
        static::assertArrayNotHasKey('count', $actual);
    }

    public function test_assoc_from_string()
    {
        ### array_fill_object($obj, $array)

        $obj = $this->lib->cast_array_to_object($this->lib->assoc_from_string('name:Greg, location:Vancouver, cat:Julius'));
        static::assertEquals(
            [
                'name'     => 'Greg',
                'location' => 'Vancouver',
                'cat'      => 'Julius',
            ],
            $this->lib->cast_object_as_array($obj)
        );
        $obj = $this->lib->fill_object($obj, $this->lib->assoc_from_string('need:Coffee'));
        static::assertEquals(
            [
                'name'     => 'Greg',
                'location' => 'Vancouver',
                'cat'      => 'Julius',
                'need'     => 'Coffee',
            ],
            $this->lib->cast_object_as_array($obj));

    }

    public function test_collapse()
    {
        $array = [
            'main'     => [
                'one'   => 100,
                'two'   => 200,
                'three' => 300,
                'four'  => 'Four Hundred',
            ],
            'override' => [
                'one'  => -1,
                'four' => 400,
                'code' => 'success',
            ],
        ];

        $expected = [
            'one'   => -1,
            'two'   => 200,
            'three' => 300,
            'four'  => 400,
            'code'  => 'success',
        ];

        // should collapse outer keys and overwrite identical keys with latest value
        static::assertEquals($expected, $this->lib->collapse($array));
        // there should be no result when collapsing the $expected array: it has only outer keys.
        static::assertEmpty($this->lib->collapse($expected));

        // should work for object values
        // add a new outer key ('object') and define an object value
        $array['object'] = new Ordinary(['one' => 1, 'code' => 'object override']);

        $expected = [
            'one'   => 1,
            'two'   => 200,
            'three' => 300,
            'four'  => 400,
            'code'  => 'object override',
        ];

        // the object's 'code' and 'one' values should override all others
        static::assertEquals($expected, $this->lib->collapse($array));

    }

    public function test_expand_segments_and_from_notation()
    {
        // creates the array and separates the key from the tree
        static::assertEquals(['this', ['thing' => 'exists']], $this->lib->expand_segments('this.thing', 'exists'));

        // builds the array normally using dot notation
        $this->lib->from_notation('this.thing', $array, 'exists');
        static::assertEquals(['this' => ['thing' => 'exists']], $array);

    }

    public function test_extract_column_to_make_compare()
    {
        ### array_extract_list($find_key, $array)

        $records = [
            'George' => ['age' => 26, 'gender' => 'Male'],
            'Lois'   => ['age' => 32, 'gender' => 'Female'],
        ];
        static::assertEquals([26, 32], $this->lib->extract_column($records, 'age'));

        ### (simple) array_make_compare_list(array $array)

        $worker = $this->lib->assoc_from_string('name:Laura, access:Administrator');
        static::assertEquals(
            [
                'name=`Laura`',
                'access=`Administrator`',
            ],
            $this->lib->make_compare($worker)
        );
        # empty returns null
        static::assertNull($this->lib->make_compare([]));
        # list returns null on invalid array (must be associative)
        static::assertNull($this->lib->make_compare(['bad']));
    }

    public function test_flatten()
    {
        // flattens an associative array down to its terminal values
        static::assertEquals(['One', 2, 'now', 'then'], $this->lib->flatten($this->source_array));
    }

    public function test_insert_before()
    {
        $expected1 = [
            'Apples'    => 'One',
            'Beets'     => 2,
            'Chocolate' => [
                'dark' => 'good',
            ],
            'Candy'     => [
                'start' => 'now',
                'end'   => 'then',
            ],
        ];

        $expected2 = [
            'Chocolate' => [
                'dark' => 'good',
            ],
            'Apples'    => 'One',
            'Beets'     => 2,
            'Candy'     => [
                'start' => 'now',
                'end'   => 'then',
            ],
        ];

        static::assertEquals($expected1,
            $this->lib->insert_before('Candy', $this->source_array, 'Chocolate', ['dark' => 'good']));
        static::assertEquals($expected2,
            $this->lib->insert_before('Apples', $this->source_array, 'Chocolate', ['dark' => 'good']));
    }

    public function test_object_array_functions()
    {
        ### array_to_object

        $obj = $this->lib->cast_array_to_object($this->source_array);
        static::assertEquals($obj->Apples, $this->source_array['Apples']);

        ### object_to_array

        $array = $this->lib->cast_object_as_array($obj);
        static::assertEquals($this->source_array, $array);

        ### copy_object_to_array

        $obj2 = $this->lib->cast_array_to_object(
            [
                'a' => 'not much',
            ]
        );
        $obj1 = $this->lib->cast_array_to_object(
            [
                'apples'      => 10,
                'beets'       => 'nope',
                'candy.start' => 'now',
                'candy.end'   => 'never',
                'value_obj'   => $obj2,
            ]
        );
        $obj_array = $this->lib->array_from_object($obj1);
        static::assertEquals(
            [
                'apples'      => 10,
                'beets'       => 'nope',
                'candy.start' => 'now',
                'candy.end'   => 'never',
                'value_obj'   => ['a' => 'not much'],
            ],
            $obj_array
        );
    }

    public function test_values()
    {
        ### generate_object_value_hash($object, $value)

        $obj = new \stdClass();

        static::assertEquals(
            [
                'stdClass' => $this->lib->assoc_from_string('one:1, two:2, three:3, four:4'),
            ],
            $this->lib->value_class($obj, $this->lib->assoc_from_string('one:1, two:2, three:3, four:4'))
        );
        # non-object returns null
        /** @noinspection PhpParamsInspection */
        static::assertNull($this->lib->value_class('not an object', $this->lib->assoc_from_string('one:1, two:2, three:3, four:4')));

        ### pivot_array_on_index(array $input)

        $worker = [
            [
                'name' => 'Google',
                'url'  => 'https://google.com',
            ],
            [
                'name' => 'Yahoo!',
                'url'  => 'http://yahoo.com',
            ],
        ];
        static::assertEquals(
            [
                'name' =>
                    [
                        'Google',
                        'Yahoo!',
                    ],
                'url'  =>
                    [
                        'https://google.com',
                        'http://yahoo.com',
                    ],
            ],
            $this->lib->pivot_array($worker)
        );

        ###  multi_explode(array $delimiters, $string, $trim)

        static::assertEquals(
            [
                0 => 'This is a string',
                1 => ' Break it up',
                2 => ' Ok?',
            ],
            $this->lib->multi_explode('This is a string. Break it up! Ok?', ['.', '!'])
        );

        ### convert_list_to_indexed_array($array)

        static::assertEquals(
            [
                0 => 'one',
                1 => 'two',
            ],
            $this->lib->array_to_numeric_index($this->lib->array_from_string('one two'))
        );

    }

}
