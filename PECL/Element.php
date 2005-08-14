<?php
/**
 * Abstract base class for all PHP code elements
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * include
 */
require_once "CodeGen/Element.php";

/**
 * Abstract base class for all PHP code elements
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
abstract class CodeGen_PECL_Element 
  extends CodeGen_Element
{

    /**
     * Checks whether a string is a valid PHP type name and returns the official name
     *
     * @access public
     * @param  string Type name
     * @return string The official type name or boolean false if not a type
     */
    function isType($name) 
    {
        static $types = array("void"     => "void", 
                              "bool"     => "bool", 
                              "boolean"  => "bool", 
                              "int"      => "int", 
                              "integer"  => "int", 
                              "float"    => "float", 
                              "double"   => "float", 
                              "real"     => "float", 
                              "string"   => "string", 
                              "array"    => "array",
                              "object"   => "object",
                              "resource" => "resource",
                              "mixed"    => "mixed",
                              "callback" => "callback",
                              "stream"   => "stream"
                              );
        
        if (isset($types[$name])) {
            return $types[$name];
        } else {
            return false;
        }
    }


    /**
     * Checks whether a string is a reserved name
     *
     * @access public
     * @param  string name
     * @return bool   true if reserved
     */
    function isKeyword($name) 
    {
        // these are taken from zend_language_scanner.l
        static $reserved = array(
                                 "abstract",
                                 "and",
                                 "array",
                                 "as",
                                 "break", 
                                 "case",
                                 "catch",
                                 "class",
                                 "const",
                                 "continue",
                                 "declare",
                                 "default",
                                 "die",
                                 "do",
                                 "echo",
                                 "else", 
                                 "elseif",
                                 "empty",
                                 "enddeclare",
                                 "endfor",
                                 "endforeach",
                                 "endif",
                                 "endwhile",
                                 "eval",
                                 "exit",
                                 "extends",
                                 "final",
                                 "for", 
                                 "foreach", 
                                 "function", 
                                 "global",
                                 "if", 
                                 "implements",
                                 "include",
                                 "include_once",
                                 "instanceof",
                                 "interface",
                                 "isset",
                                 "list",
                                 "new",
                                 "or",
                                 "print",
                                 "private", 
                                 "protected", 
                                 "public", 
                                 "require",
                                 "require_once",
                                 "return", 
                                 "static",
                                 "throw",
                                 "try",
                                 "unset",
                                 "unset",
                                 "use",
                                 "var",
                                 "while", 
                                 "xor",
                                 );
        
        foreach ($reserved as $keyword) {
            if (!strcasecmp($keyword, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate Editor settings block for C source files
     *
     * @access public
     * @return string Editor settings comment block
    */
    static function cCodeEditorSettings() 
  {
            return '
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
';
    }


    /**
     * Generate Editor settings block for documentation files
     *
     * @access public
     * @param  int    Directory nesting depth of target file (default: 3)
     * @return string Editor settings comment block
    */
    static function docEditorSettings($level=3) 
    {
        return '
<!-- Keep this comment at the end of the file
Local'.' variables:
mode: sgml
sgml-omittag:t
sgml-shorttag:t
sgml-minimize-attributes:nil
sgml-always-quote-attributes:t
sgml-indent-step:1
sgml-indent-data:t
indent-tabs-mode:nil
sgml-parent-document:nil
sgml-default-dtd-file:"'.str_repeat("../",$level).'manual.ced"
sgml-exposed-tags:nil
sgml-local-catalogs:nil
sgml-local-ecat-files:nil
End:
vim600: syn=xml fen fdm=syntax fdl=2 si
vim: et tw=78 syn=sgml
vi: ts=1 sw=1
-->
';
    }

}

?>
