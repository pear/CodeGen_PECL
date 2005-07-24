<?php
/**
 * Class representing a library dependency
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

require_once "CodeGen/PECL/Element.php";

/**
 * Class representing a library dependencyp
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_Lib
    extends CodeGen_Element
{
    private $name;

    private $path = "lib";

    private $platform;

    private $function = false;

    function __construct($name, $platform = "all")
    {
        // TODO check name
        $this->name = $name;

        $this->platform = new CodeGen_Tools_Platform($platform);
    }

    function setPath($path) 
    {
        $this->path = $path;
    }

    function setFunction($function)
    {
        $this->function = $function;
    }
    
    function getName()
    {
        return $this->name;
    }
    
    function testPlatform($name) 
    {
        return $this->platform->test($name);
    }

    function configm4($extName, $withName)
    {
        static $first = true;

        $extUpname  = strtoupper($extName);
        $withUpname = strtoupper($withName);

        if (!$this->platform->test("unix")) {
            return "";
        }

        $ret = "";

        if ($first) {
            $ret.= "  PHP_SUBST({$extUpname}_SHARED_LIBADD)\n\n";
            $first = false;
        }
        
        $ret.= "  PHP_ADD_LIBRARY_WITH_PATH({$this->name}, \$PHP_{$withUpname}_DIR/{$this->path}, {$extUpname}_SHARED_LIBADD)\n";
            
        if ($this->function) {
            $ret.= "  AC_CHECK_LIB({$this->name}, {$this->function}, [AC_DEFINE(HAVE_{$extUpname},1,[ ])], [AC_MSG_ERROR({$this->name} library not found or wrong version)],)\n";
        }
        
        return $ret;
    }

    function configw32($extName, $withName)
    {
        if (!$this->platform->test("windows")) {
            return "";
        }

        $extUpname = strtoupper($extName);
      
        return "
  if (!CHECK_LIB(\"{$this->name}.lib\", \"{$extName}\", PHP_$extUpname)) { 
    ERROR(\"{$extName}: library '{$this->name}' not found\");
  }
";        
    }
}

?>