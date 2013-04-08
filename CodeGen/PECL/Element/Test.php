<?php
/**
 * Class for testfile generation as needed for 'make test'
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
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class for testfile generation as needed for 'make test'
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Test
    extends CodeGen_PECL_Element
{
    /**
     * Constructor
     *
     * @access public
     * @param  string  testfile basename
     */
    function __construct()
    {
    }

    /**
     * testfile basename
     *
     */
    protected $name = "";

    /**
     * Setter for testcase name
     *
     * @access public
     * @return string  value of
     */
    function setName($name)
    {
        if (! preg_match('|^[\w-]+$|i', $name)) {
            return PEAR::raiseError("'$name' is not a valid test case basename");
        }

        $this->name = $name;
        if (empty($this->title)) {
            $this->title = $name;
        }
    }

    /**
     * Getter for testcase name
     *
     * @access public
     * @return string  value of
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Testcase title
     *
     * @var   string
     */
    protected $title = "";

    /**
     * Getter for testcase title
     *
     * @access public
     * @return string  value of
     */
    function getTitle()
    {
        return $this->title;
    }

    /**
     * Setter for testcase title
     *
     * @access public
     * @param  string  new value for
     */
    function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Testcase description
     *
     * @var   string
     */
    protected $description = "";

    /**
     * Getter for testcase description
     *
     * @access public
     * @return string  value of
     */
    function getDescription()
    {
        return $this->description;
    }

    /**
     * Setter for testcase description
     *
     * @access public
     * @param  string  new value for
     */
    function setDescription($text)
    {
        $this->description = $text;
    }

    /**
     * php.ini settings for this test
     *
     * @var   string
     */
    protected $ini = "";

    /**
     * Getter for php.ini settings
     *
     * @access public
     * @return string  value of
     */
    function getIni()
    {
        return $this->ini;
    }

    /**
     * Setter for php.ini settings
     *
     * @access public
     * @param  string  new value for
     */
    function setIni($code)
    {
        $this->ini = $code;
    }

    /**
     * Adder for php.ini settings
     *
     * @access public
     * @param  string  new value for
     */
    function addIni($code)
    {
        $this->ini.= "\n$code";
    }

    /**
     * Test code to decide whether to skip a test
     *
     * @var   string
     */
    protected $skipif = "";

    /**
     * Getter for skipif test code
     *
     * @access public
     * @return string  value of
     */
    function getSkipif()
    {
        return $this->skipif;
    }

    /**
     * Setter for skipif testcode
     *
     * @access public
     * @param  string  new value for
     */
    function setSkipIf($code)
    {
        $this->skipif = "";
        $this->addSkipIf($code);
    }

    /**
     * Setter for skipif testcode
     *
     * @access public
     * @param  string  new value for
     */
    function addSkipIf($code, $reason='')
    {
        // check whether $code is just an expression or a complete code snippet
        $isExpression = true;
        foreach (token_get_all("<?php $code") as $token) {
            if (($token === ';') || ($token === '}')) {
                $isExpression = false;
                break;
            }
        }

        if ($isExpression) {
            $this->skipif.= "\nif($code) die('skip $reason');\n";
        } else {
            $this->skipif.= "\n$code";
        }
    }

    /**
     * GET data
     *
     * @access protected
     * @var   string
     */
    protected $get = false;

    /**
     * Getter for GET data
     *
     * @access public
     * @return string  value of
     */
    function getGet()
    {
        return $this->get;
    }

    /**
     * Setter for GET data
     *
     * @access public
     * @param  string  new value for
     */
    function setGet($data)
    {
        $this->get = $data;
    }

    /**
     * raw POST data
     *
     * @access protected
     * @var   string
     */
    protected $post = false;

    /**
     * Getter for raw POST data
     *
     * @access public
     * @return string  value of
     */
    function getPost()
    {
        return $this->post;
    }

    /**
     * Setter for raw POST data
     *
     * @access public
     * @param  string  new value for
     */
    function setPost($data)
    {
        $this->post = $data;
    }

    /**
     * actual test code
     *
     * @var   string
     */
    protected $code;

    /**
     * Getter for test code
     *
     * @access public
     * @return string  value of
     */
    function getCode()
    {
        return $this->code;
    }

    /**
     * Setter for test code
     *
     * @access public
     * @param  string  new value for
     */
    function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * expected output for test code
     *
     * @var   string
     */
    protected $output = "OK";

    /**
     * Getter for expected output
     *
     * @access public
     * @return string  value of
     */
    function getOutput()
    {
        return $this->output;
    }

    /**
     * Setter for expected output
     *
     * @access public
     * @param  string  new value for
     */
    function setOutput($data)
    {
        $this->output = $data;
    }

    /**
     * Output block style
     *
     * @var   string
     */
    protected $mode = "plain";

    /**
     * Getter for output block style
     *
     * @access public
     * @return string  value of
     */
    function getMode()
    {
        return $this->mode;
    }

    /**
     * Setter for output block style
     *
     * @access public
     * @param  string  new value for
     */
    function setMode($mode)
    {
        switch ($mode) {
        case 'plain':
        case 'format':
        case 'regex':
            $this->mode = $mode;
            return true;
        }

        return PEAR::raiseError("'$mode' is not a valid test output comparison mode");
    }

    /**
     * all required properties set?
     *
     * @access public
     * @return bool
     */
    function complete()
    {
        if (empty($this->code))   return PEAR::raiseError("no code specified for test case");
        if (empty($this->output)) return PEAR::raiseError("no output specified for test case");
        return true;
    }

    /**
     * generate testcase file
     *
     * @access public
     * @param  object  the complete extension context
     */
    function writeTest($extension)
    {
        $extName = $extension->getName();

        $filename = "tests/{$this->name}.phpt";

        $extension->addPackageFile("test", $filename);

        $file = new CodeGen_Tools_Outbuf($extension->dirpath."/".$filename);

        echo "--TEST--\n{$this->title}\n";

        if (!empty($this->description)) {
            echo "--DESCRIPTION--\n{$this->description}\n";
        }

        if (!empty($this->ini)) {
            echo "--INI--\n{$this->ini}\n";
        }

        if (!empty($this->skipif)) {
            echo "--SKIPIF--\n<?php \n{$this->skipif}\n ?>\n";
        }

        if (!empty($this->post)) {
            echo "--POST--\n{$this->post}\n";
        }

        if (!empty($this->get)) {
            echo "--GET--\n{$this->get}\n";
        }

        echo "--FILE--\n<?php\n{$this->code}\n?>\n";

        switch ($this->mode) {
        case 'regex':
            echo "--EXPECTREGEX--\n";
            break;
        case 'format':
            echo "--EXPECTF--\n";
            break;
        case 'plain':
        default:
            echo "--EXPECT--\n";
            break;
        }
        echo $this->output;

        $file->write();
    }
}

