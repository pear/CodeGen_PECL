<?php
/**
 * Class representing a --with configure option
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
require_once "CodeGen/PECL/Element.php";

/**
 * Class representing a --with configure option
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_With
    extends CodeGen_Element
{
    /**
     * Set option name
     *
     * @var    name
     * @access private
     */
    protected $name = false;

    /**
     * Short Summary
     *
     * @var    string
     * @access private
     */
    protected $summary = "";

    /**
     * Long Description
     *
     * @var    string
     * @access private
     */
    protected $description = "";

    /**
     * A file to test for to check a given argument path
     *
     * @var    string
     * @access private
     */
    protected $testfile = false;

    /**
     * Default search path
     *
     * @var    string
     * @access private
     */
    protected $defaults = "/usr:/usr/local";

    /**
     * dependant libraries
     *
     * @var    string
     * @access private
     */
    protected $libs = array();


    /**
     * dependant header files
     *
     * @var    string
     * @access private
     */
    protected $headers = array();

    /**
     * operation mode
     *
     * @var  string
     */
    protected $mode = "default";

    /**
     * name getter
     * 
     * @param string
     */
    function getName()
    {
        return $this->name;
    }


    /**
     * name setter
     *
     * @param  string
     */
    function setName($name)
    {
        if (!preg_match('|^[a-z][a-z0-9_-]*$|i', $name)) {
            return PEAR::raiseError("'$name' is not a valid --with option name");
        }

        $this->name = $name;

        return true;
    }

    /**
     * summary setter
     *
     * @param string
     */
    function setSummary($text)
    {
        $this->summary = trim($text);
      
        return true;
    }

    /**
     * summary getter
     *
     * @return string
     */
    function getSummary()
    {
        return $this->summary ? $this->summary : "whether {$this->name} is available";
    }

    /**
     * description setter
     *
     * @param string
     */
    function setDescription($text)
    {
        $this->description = $text;

        return true;
    }

    /**
     * testfile setter
     *
     * @param string
     */
    function setTestfile($path) 
    {
        $this->testfile = $path;
    }

    /**
     * testfile getter
     * 
     * @return string
     */
    function getTestfile()
    {
        return $this->testfile;
    }

    /**
     * default searchpath setter
     *
     * @param string
     */
    function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * default searchpath getter
     * 
     * @return string
     */
    function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * mode setter
     *
     * @param string
     */
    function setMode($mode)
    {
      switch ($mode) {
      case "default":
      case "pkg-config":
        $this->mode = $mode;
        return true;

      default:
        return PEAR::raiseError("'$mode' is not a valid <with> mode");
      }
    }
    
    /**
     * add library dependency
     * 
     * @param  object
     */
    function addLib(CodeGen_PECL_Dependency_Lib $lib)
    {
        $name = $lib->getName();
        
        if (isset($this->libs[$name])) {
            return PEAR::raiseError("library '$name' specified twice");
        }

        $this->libs[$name] = $lib;

        return true;
    }

    /**
     * libraries getter
     *
     * @return array
     */
    function getLibs()
    {
        return $this->libs;
    }

    /** 
     * add header dependency
     *
     * @param object
     */
    function addHeader(CodeGen_PECL_Dependency_Header $header)
    {
        $name = $header->getName();
        
        if (isset($this->headers[$name])) {
            return PEAR::raiseError("header '$name' specified twice");
        }

        $this->headers[$name] = $header;

        return true;
    }

    /**
     * headers getter
     *
     * @return array
     */
    function getHeaders()
    {
        return $this->headers;
    }

    /** 
     * m4 PHP_ARG_WITH line
     *
     * @parameter string  optional help text
     * @return    string
     */
    function m4Line() 
    {
      $optname = str_replace("_", "-", $this->name);

      return sprintf("PHP_ARG_WITH(%s, %s,[  %-20s With %s support])\n",
                     $optname,
                     $this->getSummary(),
                     sprintf("--with-%s[=DIR]", $optname),
                     $this->name);
    }

    
    /**
     * config.m4 code snippet
     *
     * @return string
     */
    function configm4(CodeGen_PECL_Extension $extension) 
    {
        $code = "\n";

        $withName   = str_replace("-", "_", $this->getName());
        $withUpname = strtoupper($withName);
        $extName    = $extension->getName();
        $extUpname  = strtoupper($extName);
        
        if ($withName != $extName) {
            $code.= $this->m4Line()."\n\n";
        }
        
        switch ($this->mode) {
        case "pkg-config":
          // TODO support --with-pkgconfig
          // TODO check "--exists" first
          // TODO add version checks
          $code.= "  if test -z \"\$PKG_CONFIG\"; then\n";
          $code.= "    AC_PATH_PROG(PKG_CONFIG, pkg-config, no)\n";
          $code.= "  fi\n";
          $code.= "  PHP_EVAL_INCLINE(`\$PKG_CONFIG --cflags-only-I $withName`)\n";
          $code.= "  PHP_EVAL_LIBLINE(`\$PKG_CONFIG --libs $withName`, {$extUpname}_SHARED_LIBADD)\n\n";
          break;

        default:
            if ($this->testfile) {
            $code.= "
  if test -r \"\$PHP_$withUpname/".$this->testfile."\"; then
    PHP_{$withUpname}_DIR=\"\$PHP_$withUpname\"
  else
    AC_MSG_CHECKING(for ".$this->name." in default path)
    for i in ".str_replace(":"," ",$this->getDefaults())."; do
      if test -r \"\$i/".$this->testfile."\"; then
        PHP_{$withUpname}_DIR=\$i
        AC_MSG_RESULT(found in \$i)
        break
      fi
    done
    if test \"x\" = \"x\$PHP_{$withUpname}_DIR\"; then
      AC_MSG_ERROR(not found)
    fi
  fi

";
            }
            
            $pathes = array();
            foreach($this->getHeaders() as $header) {
              $pathes[$header->getPath()] = true;
            }
            foreach (array_keys($pathes) as $path) {
              $code .="  PHP_ADD_INCLUDE(\$PHP_{$withUpname}_DIR/$path)\n";
            }       
            break;
        }

        $code.= "\n";
        $code.= "  export OLD_CPPFLAGS=\"\$CPPFLAGS\"\n";
        $code.= "  export CPPFLAGS=\"\$CPPFLAGS \$INCLUDES -DHAVE_$withUpname\"\n";
        
        foreach($this->headers as $header) {
            $code.= $header->configm4($extName, $this->name);
        }  

        foreach ($this->getLibs() as $lib) {
            $code.= $lib->configm4($extName, $this->name);
        }
            
        $code.= "  export CPPFLAGS=\"\$OLD_CPPFLAGS\"\n";     

        return $code."\n";
    }
    
}

?>
