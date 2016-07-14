<?php

/**
 * **Debugging assets.**
 *
 * @package Nine One One
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Collections\Scope;
use Nine\Library\Lib;

if (defined('DEBUG_HELPERS_LOADED')) {
    return TRUE;
}

define('DEBUG_HELPERS_LOADED', TRUE);

if ( ! function_exists('location_from_backtrace')) {

    /**
     * @param $index
     *
     * @return string
     */
    function location_from_backtrace($index = 2)
    {
        $file = '';
        $line = 0;
        $dbt = debug_backtrace();

        if (array_key_exists($index, $dbt)) {

            if (array_key_exists('file', $dbt[$index])) {
                $file = basename($dbt[$index]['file']);
                $line = $dbt[$index]['line'];
            }

            $function = array_key_exists('function', $dbt[$index]) ? $dbt[$index]['function'] : 'λ()';
            $class = array_key_exists('class', $dbt[$index]) ? $dbt[$index]['class'] : '(λ)';

            return $file === '' ? "λ()~>$class::$function" : "$file:$line~>$class::$function";
        }

        return '[unidentifiable context]';
    }

}

if ( ! function_exists('og_dump')) {

    /**
     * @param     $var
     * @param int $depth
     *
     * @return mixed
     */
    function og_dump($var = NULL, $depth = 12)
    {
        \Tracy\Debugger::$maxDepth = $depth;

        $args = func_get_args();
        Lib::array_forget($args, func_num_args() - 1);

        $dumper = defined('PHPUNIT_TESTS__') ? 'Tracy\Dumper::toTerminal' : 'Tracy\Debugger::dump';

        array_map($dumper, $args);

        return $var;
    }

}

if ( ! function_exists('expose')) {

    /**
     * @param null $value
     * @param int  $depth
     */
    function expose($value = NULL, $depth = 8)
    {
        $guard = location_from_backtrace(1);

        $trace = ['probe' => $guard, 'trace' => [], 'debug' => $value];
        $fence = 2;

        $lfb = location_from_backtrace($fence);
        while ($guard !== $lfb) {
            $trace['trace'][$fence] = $lfb;
            $guard = $lfb;
            ++$fence;
            $lfb = location_from_backtrace($fence);
        }

        og_dump($trace, $depth);
    }
}

if ( ! function_exists('ddump')) {

    /**
     * dump and die with backtrace
     *
     * @param     $value
     * @param int $depth
     */
    function ddump($value = NULL, $depth = 8)
    {
        expose($value, $depth);

        if ( ! defined('PHPUNIT_TESTS__')) {
            exit(1);
        }
    }
}

if ( ! function_exists('readable_error_type')) {

    /**
     * @param $error_type
     *
     * @return string
     */
    function readable_error_type($error_type) : string
    {
        switch ($error_type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }

        return 'UNKNOWN_ERROR';
    }
}

if ( ! function_exists('vdump')) {

    function vdump($value)
    {
        $guard = location_from_backtrace(1);

        $trace = ['probe' => $guard, 'trace' => [], 'target' => $value];
        $fence = 2;

        $lfb = location_from_backtrace($fence);
        while ($guard !== $lfb) {
            $trace['trace'][$fence] = $lfb;
            $guard = $lfb;
            ++$fence;
            $lfb = location_from_backtrace($fence);
        }

        ob_start();

        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($value);
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($trace);

        $vd = ob_get_clean();
        echo "<pre>$vd";

        exit();
    }

}

if ( ! function_exists('closure_dump')) {
    /**
     * @param Closure $c
     * @param bool    $short
     *
     * @return string
     */
    function closure_dump(Closure $c, $short = TRUE) : string
    {
        $str = 'function (';
        $r = new ReflectionFunction($c);

        $params = [];
        foreach ($r->getParameters() as $p) {
            $s = '';
            if ($p->isArray()) {
                $s .= 'array ';
            }
            else if ($p->getClass()) {
                $s .= $p->getClass()->name . ' ';
            }

            if ($p->isPassedByReference()) {
                $s .= '&';
            }

            $s .= '$' . $p->name;

            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
            }
            $params [] = $s;
        }

        if ($short) {
            $filename = $r->getFileName();
            $start_line = $r->getStartLine();
            $end_line = $r->getEndLine();

            //$parameters = implode(', ', $params);

            return "in $filename at $start_line ~> $end_line";
        }

        $str .= implode(', ', $params);
        $str .= '){' . PHP_EOL;
        $lines = file($r->getFileName());
        for ($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
            $str .= $lines[$l];
        }

        return $str;
    }
}

if ( ! function_exists('find_functions')) {

    function find_functions($source_filename) : array
    {
        # The Regular Expression for Function Declarations
        $functionFinder = '/function[\s\n]+(\S+)[\s\n]*\(/';

        # Init an Array to hold the Function Names
        $functionArray = [];

        # Load the Content of the PHP File
        $fileContents = file_get_contents($source_filename);

        # Apply the Regular Expression to the PHP File Contents
        preg_match_all($functionFinder, $fileContents, $functionArray);

        # If we have a Result, Tidy It Up
        if (count($functionArray) > 1) {

            # Grab Element 1, as it has the Matches
            $functionArray = $functionArray[1];
        }

        sort($functionArray);

        return $functionArray;
    }
}

if ( ! function_exists('document_functions')) {

    function document_functions(array $functions) : Scope
    {
        $functions_list = [];

        foreach ($functions as $func) {
            $f = new ReflectionFunction($func);

            $doc_comment = $f->getDocComment();

            $args = [];

            foreach ($f->getParameters() as $param) {
                $temp_arg = '';

                if ($param->isPassedByReference()) {
                    $temp_arg = '&';
                }

                if ($param->isOptional()) {
                    $display_value = $default_value = gettype($param->getDefaultValue());

                    switch ($default_value) {
                        case 'string':
                            $display_value = "'" . $param->getDefaultValue() . "'";
                            break;
                        case 'array':
                            $display_value = '[]';
                            break;
                        case 'integer':
                            $display_value = $param->getDefaultValue();
                            break;
                        case 'boolean':
                            $display_value = $param->getDefaultValue() ? 'true' : 'false';
                            break;
                        default :
                            break;
                    }

                    $temp_arg = $temp_arg . '$' . $param->getName() . ' = ' . $display_value;
                }
                else {
                    $temp_arg .= '$' . $param->getName();
                }

                $args[] = $temp_arg;

                unset ($temp_arg);
            }

            $functions_list[] = ['comment' => $doc_comment, 'function' => $func . '(' . implode(', ', $args) . ')'];
        }

        return new Scope($functions_list);
    }
}
