<?php namespace Nine\Library;

/**
 * @package Radium Codex
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

/**
 * Test the framework support functions
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes disabled
 */
class StringHelpersTest extends \PHPUnit_Framework_TestCase
{
    /** @var Lib */
    private $lib;

    public function setUp()
    {
        $this->lib = new Lib;
    }

    public function test_str_has_to_alias_from_class()
    {
        ### contains($needles, $haystack)

        $this->assertTrue($this->lib->str_has('this exists', 'Testing to see if this exists.'));
        $this->assertFalse($this->lib->str_has('this does not exist', 'Testing to see if this exists.'));

        ### slug_to_title($slug)

        $this->assertEquals('Imagine That This Is A Slug', $this->lib->slug_to_title('imagine-that-this-is-a-slug'));

        ### remove_namespace($class_name, $class_suffix = NULL)

        $this->assertEquals('Strings', $this->lib->remove_namespace(Strings::class));
        $this->assertEquals('Strings', $this->lib->remove_namespace(Strings::class, 'F9'));

        ### name_from_class($class_name, $suffix_to_remove = 'HttpController')

        $this->assertEquals('applicationcontroller', $this->lib->alias_from_class('F9\ApplicationController', ''));
        $this->assertEquals('application', $this->lib->alias_from_class('F9\ApplicationController', 'Controller'));

    }

    public function test_starts_and_ends_with()
    {
        ### startsWith($needle, $haystack)

        $this->assertTrue($this->lib->starts_with('Odd', 'Odd Greg'));
        $this->assertFalse($this->lib->starts_with('Greg', 'Odd Greg'));

        ### endsWith($needle, $haystack)

        $this->assertTrue($this->lib->ends_with('Greg', 'Odd Greg'));
        $this->assertFalse($this->lib->ends_with('Odd', 'Odd Greg'));

        ### stripTrailing($characters, $string)

        $this->assertEquals('All_The_Things', $this->lib->strip_tail('_', 'All_The_Things__'));
        $this->assertNotEquals('All_The_Things', $this->lib->strip_tail('_', 'All_The_Things--'));

        ### truncate($string, $endlength = "30", $end = "...")

        $this->assertEquals('A line tha...',
            $this->lib->truncate(
                "A line that is in need of shortening and I ain't talking about cooking.",
                $endlength = '10',
                $end = '...'
            )
        );

    }

    public function test_changing_cases()
    {
        ### snakecase_to_heading($word, $space = ' ')

        $this->assertEquals('No Way Bob', $this->lib->snake_to_heading('no_way_bob'));
        $this->assertEquals('No&nbsp;Way&nbsp;Bob', $this->lib->snake_to_heading('no_way_bob', '&nbsp;'));

        ### snakecase_to_camelcase($string)

        $this->assertEquals('noWayBob', $this->lib->snake_to_camel('no_way_bob', true));

        ### camel_to_snakecase($input, $delimiter = '_')

        $this->assertEquals('not_a_chance_bob', $this->lib->camel_to_snake('notAChanceBob', $delimiter = '_'));

    }

    public function test_transformations()
    {
        ### remove_quotes($string)

        $this->assertEquals('A string chock full of quotes', $this->lib->remove_quotes('A "string" \'chock full\' of \'"quotes"\''));

        ### generate_token($length = 16)

        $this->assertNotSame($this->lib->generate_token(), $this->lib->generate_token());
        # generate_token generates HEX pairs, thus a length of 10 == 20 in the result
        $this->assertSame(strlen($this->lib->generate_token(10)), 20);

        ### e($value)

        $this->assertEquals('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', $this->lib->entities("A 'quote' is <b>bold</b>"));

        ### h($string, $double_encode = TRUE)

        /** @noinspection HtmlUnknownTarget */
        $this->assertEquals("&lt;a href='test'&gt;Test&lt;/a&gt;", $this->lib->hsc("<a href='test'>Test</a>"));

    }

    public function test_parsing()
    {
        ### encode_readable_json($to_convert, $indent = 0)

        $readable_json = file_get_contents(__DIR__ . '/../readable_jason.json');
        $this->assertEquals($readable_json, $this->lib->encode_readable_json(
            [
                'a' => 1,
                'b' => 'stuff',
                'c' => ['d' => TRUE],
                'n' => NULL,
            ]
        )
        );

        ### parse_class_name($name)

        $expect = [
            'namespace'      =>
                [
                    0 => 'Symfony',
                    1 => 'Component',
                    2 => 'HttpFoundation',
                ],
            'class_name'     => 'AcceptHeader',
            'namespace_path' => 'Symfony\\Component\\HttpFoundation',
            'namespace_base' => 'Symfony',
        ];

        $this->assertEquals($expect, $this->lib->parse_class_name('Symfony\Component\HttpFoundation\AcceptHeader'));
    }

    public function test_file_in_path_to_string_to_url()
    {
        ### file_in_path($name, Array $paths)

        $this->assertStringEndsWith('readable_jason.json', $this->lib->file_in_path('readable_jason.json', [__DIR__ . '/../']));

        ### string_to_url($string)

        $this->assertEquals('the-balls-are-bouncy-eh', $this->lib->string_to_uri('The balls are bouncy, eh?'));
    }

    public function test_pattern_matches_and_str_matches()
    {
        static::assertTrue($this->lib->pattern_matches('Library/Documents/OnLine','Library/*'));
        static::assertTrue($this->lib->pattern_matches('Library/Documents/OnLine','*/Documents/*'));
        static::assertFalse($this->lib->pattern_matches('Library/Documents/OnLine','*/Books/*'));
        static::assertTrue($this->lib->pattern_matches('Library/Documents/OnLine','Library/Documents/OnLine'));
    }



}
