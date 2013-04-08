<?php
/**
 * Command wrapper class
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/Command.php";

require_once "CodeGen/PECL/Extension.php";
require_once "CodeGen/PECL/ExtensionParser.php";

/**
 * Command wrapper class
 *
 * This class wraps up the functionality needed for the
 * command line script.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Command
    extends CodeGen_Command
{
    /**
     * Command constructor
     *
     * @param object  Extension to work on
     */
    function __construct(CodeGen_Extension $extension)
    {
        parent::__construct($extension);

        if ($this->options->have("linespecs")) {
            $this->extension->setLinespecs(true);
        }
    }

    /**
     * Add pecl-gen specific command line options
     *
     * @return array  extended options
     */
    function commandOptions()
    {
        list($shortOptions, $longOptions) = parent::commandOptions();

        $longOptions= array_merge($longOptions, array("extname=",
                                                      "full-xml",
                                                      "function=",
                                                      "linespecs",
                                                      "no-help",
                                                      "proto=",
                                                      "skel=",
                                                      "stubs=",
                                                      "xml=="));

        return array($shortOptions, $longOptions);
    }

    /**
     * Show usage/help information
     *
     * @param string  otpional additional message
     */
    function showUsage($message = false)
    {
        $fp = fopen("php://stderr", "w");

        if ($message) fputs($fp, "$message\n\n");

        fputs($fp, "Usage:

pecl-gen [-h] [--force] [--experimental] [--version]
  [--extname=name] [--proto=file] [--skel=dir] [--stubs=file]
  [--no-help] [--xml[=file]] [--full-xml] [--function=proto] [specfile.xml]

  -h|--help          this message
  -f|--force         overwrite existing directories
  -d|--dir           output directory (defaults to extension name)
  -l|--lint          check syntax only, don't create output
  --linespecs        generate #line specs
  -x|--experimental  deprecated
  --function         create a function skeleton from a proto right away
  --version          show version info

  the following options are inherited from ext_skel:
  --extname=module   module is the name of your extension
  --proto=file       file contains prototypes of functions to create
  --xml              generate xml documentation to be added to phpdoc-cvs

  these wait for functionality to be implemented and are ignored for now ...
  --stubs=file       generate only function stubs in file
  --no-help          don't try to be nice and create comments in the code
                     and helper functions to test if the module compiled

  these are accepted for backwards compatibility reasons but not used ...
  --full-xml         generate xml documentation for a self-contained extension
                     (this was also a no-op in ext_skel)
  --skel=dir         path to the skeleton directory
                     (skeleton stuff is now self-contained)
");

        fclose($fp);
    }

    /**
     * Generate just a single function stub file
     *
     */
    function singleFunction()
    {
        $func = new CodeGen_PECL_Element_Function;

        $func->setRole("public");

        $err = $func->setProto(trim($this->options->value("function")), $this->extension);
        if (PEAR::isError($err)) {
            $this->terminate($err->getMessage());
        }

        $err = $this->extension->addFunction($func);
        if (PEAR::isError($err)) {
            $this->terminate($err->getMessage());
        }

        echo $this->extension->publicFunctionsC();

        echo "\n\n/*----------------------------------------------------------------------*/\n\n";

        foreach ($this->extension->getFunctions() as $name => $function) {
            echo sprintf("\tPHP_FE(%-20s, NULL)\n", $name);
        }

        echo "\n\n/*----------------------------------------------------------------------*/\n\n";

        foreach ($this->extension->getFunctions() as $name => $function) {
            echo "PHP_FUNCTION($name);\n";
        }
    }

    /**
     * ext-skel compatibility mode
     *
     */
    function extSkelCompat()
    {
        $extname = $this->options->value("extname");

        $err = $this->extension->setName($extname);
        if (PEAR::isError($err)) {
            $this->terminate($err->getMessage());
        }

        if ($this->options->have("proto")) {
            $proto_file = $this->options->value("proto");

            if (!file_exists($proto_file) || !is_readable($proto_file)) {
                $this->terminate("cannot open proto file");
            }

            foreach (file($proto_file) as $line) {
                $func = new CodeGen_PECL_Element_Function;
                $func->setRole("public");
                $err = $func->setProto(trim($line), $this->extension);
                if (PEAR::isError($err)) {
                    $this->terminate($err->getMessage());
                }

                $err = $this->extension->addFunction($func);
                if (PEAR::isError($err)) {
                    $this->terminate($err->getMessage());
                }
            }
        }

        if ($this->options->have("stubs")) {
            $stubname = $this->options->value("stubs");

            if (file_exists("$stubname")  && !$this->options->have("f", "force")) {
                $this->terminate("'$stubname' already exists (use '--force' to overwrite)");
            }

            $fp = fopen($stubname, "w");
            fputs($fp, $this->extension->publicFunctionsC());

            fputs($fp, "\n\n/*----------------------------------------------------------------------*/\n\n");

            foreach ($this->extension->functions as $name => $function) {
                fputs($fp, sprintf("\tPHP_FE(%-20s, NULL)\n", $name));
            }

            fputs($fp, "\n\n/*----------------------------------------------------------------------*/\n\n");

            foreach ($this->extension->functions as $name => $function) {
                fputs($fp, "PHP_FUNCTION($name);\n");
            }

            fclose($fp);

            echo "$stubname successfully written\n";
        } else {
            if (file_exists("./$extname")  && !$this->options->have("f", "force")) {
                $this->terminate("'$extname' already exists, can't create directory (use '--force' to override)");
            }

            $err = System::mkdir($extname);
            if (PEAR::isError($err)) {
                $this->terminate($err->getMessage());
            }

            $this->extension->dirpath = realpath("./$extname");

            $err = $this->extension->generateSource("./$extname");
            if (PEAR::isError($err)) {
                $this->terminate($err->getMessage());
            }

            if ($this->options->have("xml")) {
                $manpath = "$extname/manual/". str_replace('_', '-', $extname);

                $err = System::mkdir("-p $manpath");
                if (PEAR::isError($err)) {
                    $this->terminate($err->getMessage());
                }

                $err = $this->extension->generateDocumentation($manpath);
                if (PEAR::isError($err)) {
                    $this->terminate($err->getMessage());
                }
            }

            $this->extension->writeReadme("./$extname");

            if (!$this->options->have("quiet")) {
                echo $this->extension->successMsg();
            }
        }

    }
}

