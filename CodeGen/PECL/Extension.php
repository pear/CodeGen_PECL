<?php
/**
 * A class that generates PECL extension soure and documenation files
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
 * @version    CVS: $Id: Extension.php,v 1.75 2007/04/16 09:28:03 hholzgra Exp $
 * @version    CVS: $Id: Extension.php,v 1.75 2007/04/16 09:28:03 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_PECL
 */

/**
 * includes
 */
require_once "System.php";

require_once "CodeGen/Extension.php";

require_once "CodeGen/PECL/Release.php";

require_once "CodeGen/PECL/Element.php";
require_once "CodeGen/PECL/Element/Constant.php";
require_once "CodeGen/PECL/Element/Function.php";
require_once "CodeGen/PECL/Element/Resource.php";
require_once "CodeGen/PECL/Element/Ini.php";
require_once "CodeGen/PECL/Element/Global.php";
require_once "CodeGen/PECL/Element/Logo.php";
require_once "CodeGen/PECL/Element/Test.php";
require_once "CodeGen/PECL/Element/Class.php";
require_once "CodeGen/PECL/Element/Interface.php";
require_once "CodeGen/PECL/Element/Stream.php";

require_once "CodeGen/PECL/Dependency/With.php";
require_once "CodeGen/PECL/Dependency/Lib.php";
require_once "CodeGen/PECL/Dependency/Header.php";
require_once "CodeGen/PECL/Dependency/Extension.php";
require_once "CodeGen/PECL/Dependency/Platform.php";

/**
 * A class that generates PECL extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 1.1.3
 * @link       http://pear.php.net/package/CodeGen_PECL
 */
class CodeGen_PECL_Extension
    extends CodeGen_Extension
{
    /**
    * Current CodeGen_PECL version number
    *
    * @return string
    */
    function version()
    {
        return "1.1.3";
    }

    /**
    * CodeGen_PECL Copyright message
    *
    * @return string
    */
    function copyright()
    {
        return "Copyright (c) 2003-2006 Hartmut Holzgraefe";
    }

    // {{{ member variables

    /**
     * The public PHP functions defined by this extension
     *
     * @var array
     */
    protected $functions = array();

    /**
     * The extensions internal functions like MINIT
     *
     * @var array
     */
    protected $internalFunctions = array();

    /**
     * The constants defined by this extension
     *
     * @var array
     */
    protected $constants = array();

    /**
     * The PHP classes defined by this extension
     *
     * @var array
     */
    protected $classes = array();

    /**
     * The PHP interfaces defined by this extension
     *
     * @var array
     */
    protected $interfaces = array();

    /**
     * The extensions php.ini parameters
     *
     * @var array
     */
    protected $phpini    = array();

    /**
     * The extensions internal global variables
     *
     * @var array
     */
    protected $globals    = array();

    /**
     * The PHP resources defined by this extension
     *
     * @var array
     */
    protected $resources = array();

    /**
     * Custom test cases
     *
     * @var array
     */
    protected $testcases = array();

    /**
     * phpinfo logos
     *
     * @var    string
     * @access private
     */
    protected $logos = array();

    /**
     * cross extension dependencies
     *
     * @var  array
     */
    protected $otherExtensions = array();

    /**
     * generate #line specs?
     *
     * @var     bool
     * @access  private
     */
    protected $linespecs = false;

    /**
     * PHP Streams
     *
     * @var    array
     * @access private
     */
    protected $streams = array();

    /**
     * --with configure options
     *
     * @var    array
     * @access private
     */
    protected $with = array();

    /**
     * pear installer channel name
     *
     * @var    string
     * @access private
     */
    protected $channel = "pecl.php.net";

    /**
     * phpdoc reference purpose
     *
     * See http://doc.php.net/php/en/dochowto/x1257.php for details
     *
     * @var    string
     * @access private
     */
    protected $docPurpose = "utilspec";

    // }}}

    // {{{ constructor

    /**
     * The constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->release = new CodeGen_PECL_Release;

        $this->platform = new CodeGen_PECL_Dependency_Platform("all");

        parent::__construct();
    }

    // }}}

    // {{{ member adding functions

    /**
     * Add a function to the extension
     *
     * @access public
     * @param  object   a function object
     */
    function addFunction(CodeGen_PECL_Element_Function $function)
    {
        $name = $function->getName();
        $role = $function->getRole();

        switch ($role) {
        case "public":
            if (isset($this->functions[$name])) {
                return PEAR::raiseError("public function '$name' has been defined before");
            }
            $this->functions[$name] = $function;
            return true;

        case "private":
            return PEAR::raiseError("private functions are no longer supported, use <code> sections instead");

        case "internal":
            if (isset($this->internalFunctions[$name])) {
                return PEAR::raiseError("internal '$name' has been defined before");
            }
            $this->internalFunctions[$name] = $function;
            return true;

        default:
            return PEAR::raiseError("unnokwn function role '$role'");
        }
    }

    /**
     * Set target platform for generated code
     *
     * @access public
     * @param  string  platform name
     */
    function setPlatform($type)
    {
        $this->platform = new CodeGen_PECL_Dependency_Platform($type);
        if (PEAR::isError($this->platform)) {
            return $this->platform;
        }

        return true;
    }

    /**
     * Add a PHP constant to the extension
     *
     * @access public
     * @param  object   a constant object
     */
    function addConstant(CodeGen_PECL_Element_Constant $constant)
    {
        $name = $constant->getName();

        if (isset($this->constants[$name])) {
            return PEAR::raiseError("constant '$name' has been defined before");
        }
        $this->constants[$name] = $constant;

        return true;
    }

    /**
     * Add a PHP ini directive
     *
     * @access public
     * @param  object   a phpini object
     */
    function addPhpIni(CodeGen_PECL_Element_Ini $phpini)
    {
        $name = $phpini->getName();

        if (isset($this->phpini[$name])) {
            return PEAR::raiseError("php.ini directive '$name' has been defined before");
        }
        $this->phpini[$name] = $phpini;

        return true;
    }

    /**
     * Add a internal global variable
     *
     * @access public
     * @param  object   a global object
     */
    function addGlobal(CodeGen_PECL_Element_Global $global)
    {
        $name = $global->getName();
        if (isset($this->globals[$name])) {
            return PEAR::raiseError("global '{$name}' has been defined before");
        }
        $this->globals[$name] = $global;

        return true;
    }

    /**
     * Add a PHP resource type
     *
     * @access public
     * @param  object   a resource object
     */
    function addResource(CodeGen_PECL_Element_Resource $resource)
    {
        $name = $resource->getName();
        if (isset($this->resources[$name])) {
            return PEAR::raiseError("resource type '{$name}' has been defined before");
        }
        $this->resources[$name] = $resource;

        return true;
    }

    /**
     * Get PHP resource types
     *
     * @access public
     * @return array
     */
    function getResources()
    {
        return $this->resources;
    }

    /**
     * Get a specific resource by name
     *
     * @access public
     * @param  string  resource name
     * @return object  resource object or false if not found
     */
    function getResource($name)
    {
        if (isset($this->resources[$name])) {
            return $this->resources[$name];
        }

        return false;
    }

    /**
     * Get PHP constants
     *
     * @access public
     * @return array
     */
    function getConstants()
    {
        return $this->constants;
    }

    /**
     * Get a specific constant by name
     *
     * @access public
     * @param  string  constant name
     * @return object  constant object or false if not found
     */
    function getConstant($name)
    {
        if (isset($this->constants[$name])) {
            return $this->constants[$name];
        }

        return false;
    }

    /**
     * Get a specific class by name
     *
     * @access public
     * @param  string  class name
     * @return object  class object or false if not found
     */
    function getClass($name)
    {
        if (isset($this->classes[$name])) {
            return $this->classes[$name];
        }

        return false;
    }

    /**
     * Add a PHP class to the extension
     *
     * @access public
     * @param  object   a class object
     */
    function addClass(CodeGen_PECL_Element_Class $class)
    {
        if (isset($this->classes[$class->getName()])) {
            return PEAR::raiseError("class '".$class->getName()."' has been defined before");
        }
        $this->classes[$class->getName()] = $class;

        return true;
    }

    /**
     * Add a PHP interface to the extension
     *
     * @access public
     * @param  object   an interface object
     */
    function addInterface(CodeGen_PECL_Element_Interface $interface)
    {
        if (isset($this->interfaces[$interface->getName()])) {
            return PEAR::raiseError("interface '".$interface->getName()."' has been defined before");
        }
        $this->interfaces[$interface->getName()] = $interface;

        return true;
    }

    /**
     * Add a PHP stream wrapper to the extension
     *
     * @access public
     * @param  object   a stream wrapper object
     */
    function addStream(CodeGen_PECL_Element_Stream $stream)
    {
        if (isset($this->streams[$stream->getName()])) {
            return PEAR::raiseError("stream '".$stream->getName()."' has been defined before");
        }
        $this->streams[$stream->getName()] = $stream;

        return true;
    }

    /**
     * Add a --with configure option
     *
     * @access  public
     * @param   object 'With' object
     * @returns bool
     */
    function addWith(CodeGen_PECL_Dependency_With $with)
    {
        $name = $with->getName();

        if (isset($this->with[$name])) {
            return PEAR::raiseError("--with-{$name} declared twice");
        }

        $this->with[$name] = $with;

        return true;
    }

    /**
     * Add phpinfo logo
     *
     * @access public
     * @param  object  the logo
     */
    function addLogo(CodeGen_PECL_Element_Logo $logo)
    {
        $name = $logo->getName();

        if (isset($this->logos[$name])) {
            return PEAR::raiseError("logo '{$name}' already defined");
        }

        $this->logos[$name] = $logo;

        return true;
    }

    /**
     * Add cross-module dependency
     *
     * @param  object  extension dependency object
     */
    function addOtherExtension(CodeGen_PECL_Dependency_Extension $ext)
    {
        $name = $ext->getName();

        if (isset($this->otherExtensions[$name])) {
            return PEAR::raiseError("dependency to extension '{$name}' already defined");
        }

        $this->otherExtensions[$name] = $ext;

        return true;
    }

    /**
     * Generate #line specs?
     *
     * @access public
     * @param  bool
     */
    function setLinespecs($state)
    {
        $this->linespecs = $state;
    }

    /**
     * linespec getter
     *
     * @access public
     * @return bool
     */
    function getLinespecs()
    {
        return $this->linespecs;
    }

    // }}}

    // {{{ output generation

    /**
     * Create the extensions including
     *
     * @access public
     * @param  string Directory to create (default is ./$this->name)
     */
    function createExtension($dirpath = false, $force = false)
    {
        // default: create dir in current working directory,
        // dirname is the extensions base name
        if (!is_string($dirpath) || $dirpath == "") {
            $dirpath = "./" . $this->name;
        }

        // purge and create extension directory
        if ($dirpath !== ".") {
            if (!$force && file_exists($dirpath)) {
                return PEAR::raiseError("'$dirpath' already exists, can't create that directory (use '--force' to override)");
            } else if (!@System::mkdir("-p $dirpath")) {
                return PEAR::raiseError("can't create '$dirpath'");
            }
        }

        // make path absolute to be independant of working directory changes
        $this->dirpath = realpath($dirpath);

        // add "unknown" author if no authors specified
        if (empty($this->authors)) {
            $author = new CodeGen_PECL_Maintainer;
            $author->setUser("unknown");
            $author->setName("Unknown User");
            $author->setEmail("unknown@example.com");
            $author->setRole("lead");

            $this->addAuthor($author);
        }

        if (empty($this->description)) {
            $this->description = "none";
        }

        echo "Creating '{$this->name}' extension in '$dirpath'\n";

        // generate complete source code
        $this->generateSource();

        // copy additional source files
        if (isset($this->packageFiles['copy'])) {
            foreach ($this->packageFiles['copy'] as $targetpath => $sourcepath) {
                $targetpath = $this->dirpath."/".$targetpath;
                if (!is_dir(dirname($targetpath))) {
                    mkdir(dirname($targetpath), 0777, true);
                }
                copy($sourcepath, $targetpath);
            }
        }

        // generate README file
        $this->writeReadme();

        // generate DocBook XML documantation for PHP manual
        $manpath = $this->dirpath . "/manual/";
        if (!@System::mkdir("-p $manpath")) {
            return PEAR::raiseError("can't create '$manpath'", E_USER_ERROR);
        }
        $this->generateDocumentation($manpath);
    }

    /**
     * Create the extensions code soure and project files
     *
     * @access public
     */
    function generateSource()
    {
        // generate source and header files
        $this->writeHeaderFile();
        $this->writeCodeFile();

        foreach ($this->logos as $logo) {
            $fp = new CodeGen_Tools_FileReplacer("{$this->dirpath}/".$logo->getName()."_logos.h");
            $fp->puts($logo->hCode());
            $fp->close();
        }

        // generate project files for configure (unices and similar platforms like cygwin)
        if ($this->platform->test("unix")) {
            $this->writeConfigM4();
        }

        // generate project files for Windows platform (VisualStudio/C++ V6)
        if ($this->platform->test("windows")) {
            $this->writeMsDevStudioDsp();
            $this->writeConfigW32();
        }

        // generate .cvsignore file entries
        $this->writeDotCvsignore();

        // generate EXPERIMENTAL file for unstable release states
        $this->writeExperimental();

        // generate CREDITS file
        $this->writeCredits();

        // generate LICENSE file if license given
        if ($this->license) {
            $this->license->writeToFile($this->dirpath."/LICENSE");
            $this->files['doc'][] = "LICENSE";
        }

        // generate test case templates
        $this->writeTestFiles();

        // generate PEAR/PECL package.xml file
        $this->writePackageXml();
        $this->writePackageXml2();
    }

    // {{{   docbook documentation

    /**
     * Create the extension documentation DocBook XML files
     *
     * @access public
     * @param  string Directory to write to
     */
    function generateDocumentation($docdir)
    {
        $idName = str_replace('_', '-', $this->name);

        if (!@System::mkdir("-p $docdir/$idName")) {
            return PEAR::raiseError("can't create '$docdir/$idName'", E_USER_ERROR);
        }

        $manual = new CodeGen_Tools_FileReplacer("$docdir/manual.xml.in");
        $manual->puts("<?xml version='1.0' encoding='UTF-8' ?>
<!DOCTYPE book PUBLIC '-//OASIS//DTD DocBook XML V4.1.2//EN'
          '@PHPDOC@/dtds/dbxml-4.1.2/docbookx.dtd' [

<!-- Add translated specific definitions and snippets -->
<!ENTITY % language-defs     SYSTEM '@PHPDOC@/en/language-defs.ent'>
<!ENTITY % language-snippets SYSTEM '@PHPDOC@/en/language-snippets.ent'>

%language-defs;
%language-snippets;

<!-- Fallback to English definitions and snippets (in case of missing translation) -->
<!ENTITY % language-defs.default     SYSTEM '@PHPDOC@/en/language-defs.ent'>
<!ENTITY % language-snippets.default SYSTEM '@PHPDOC@/en/language-snippets.ent'>
<!ENTITY % extensions.default        SYSTEM '@PHPDOC@/en/extensions.ent'>

%language-defs.default;
%language-snippets.default;
%extensions.default;

<!-- All global entities for the XML files -->
<!ENTITY % global.entities  SYSTEM '@PHPDOC@/entities/global.ent'>

<!ENTITY % file.entities      SYSTEM './file-entities.ent'>

<!-- Include all external DTD parts defined previously -->
%global.entities;
%file.entities;

<!-- Autogenerated missing entites and IDs to make build work -->
<!ENTITY % missing-entities  SYSTEM '@PHPDOC@/entities/missing-entities.ent'>
%missing-entities;
]>

<book id='manual' lang='en'>
   &reference.$idName.reference;
</book>
");
        $manual->close();

        $makefile = new CodeGen_Tools_FileReplacer("$docdir/Makefile");
        $makefile->puts("#
all: html

confcheck:
\t@if test \"x$(PHPDOC)\" = \"x\"; then echo PHPDOC not set; exit 3; fi

manual.xml: manual.xml.in
\tsed -e's:@PHPDOC@:\$(PHPDOC):g' < manual.xml.in > manual.xml

html: confcheck manual.xml
\trm -rf html; mkdir html
\tSP_ENCODING=XML SP_CHARSET_FIXED=YES openjade -D $(PHPDOC) -wno-idref -c $(PHPDOC)/docbook/docbook-dsssl/catalog -c $(PHPDOC)/phpbook/phpbook-dsssl/defaults/catalog -d $(PHPDOC)/phpbook/phpbook-dsssl/html.dsl -V use-output-dir -t sgml $(PHPDOC)/phpbook/phpbook-xml/phpdocxml.dcl manual.xml

bightml: confcheck manual.xml
\trm -rf html; mkdir html
\tSP_ENCODING=XML SP_CHARSET_FIXED=YES openjade -D $(PHPDOC) -wno-idref -c $(PHPDOC)/docbook/docbook-dsssl/catalog -c $(PHPDOC)/phpbook/phpbook-dsssl/defaults/catalog -d $(PHPDOC)/phpbook/phpbook-dsssl/html.dsl -V nochunks -t sgml $(PHPDOC)/phpbook/phpbook-xml/phpdocxml.dcl manual.xml > manual.html

tex: manual.tex

manual.tex: confcheck manual.xml
\tSP_ENCODING=XML SP_CHARSET_FIXED=YES openjade -D $(PHPDOC) -wno-idref -c $(PHPDOC)/docbook/docbook-dsssl/catalog -c $(PHPDOC)/phpbook/phpbook-dsssl/defaults/catalog -d $(PHPDOC)/phpbook/phpbook-dsssl/print.dsl -t tex $(PHPDOC)/phpbook/phpbook-xml/phpdocxml.dcl manual.xml

pdf: manual.tex
\tpdfjadetex manual.tex && pdfjadetex manual.tex && pdfjadetex manual.tex
");

        $makefile->close();

        $entities = new CodeGen_Tools_FileReplacer("$docdir/file-entities.ent");

        $entities->puts("<!ENTITY reference.$idName.reference SYSTEM './$idName/reference.xml'>\n");
        $fp = new CodeGen_Tools_FileReplacer("$docdir/$idName/reference.xml");
        $fp->puts(
"<?xml version='1.0' encoding='iso-8859-1'?>
<!-- ".'$'."Revision: 1.1 $ -->
");

        // phpdoc comments according to http://doc.php.net/php/de/dochowto/x1257.php
        $fp->puts("<!-- Purpose: ".$this->docPurpose." -->\n");

        $fp->puts("<!-- Membership: pecl");
        if (count($this->with)) {
            $fp->puts(", external");
        }
        $fp->puts(" -->\n");

        if ($this->release->getState() !== 'stable') {
            $fp->puts("<!-- State: experimental -->\n");
        }

        $fp->puts("
 <reference xml:id='ref.$idName' xmlns='http://docbook.org/ns/docbook' xmlns:xlink='http://www.w3.org/1999/xlink'>
  <title>{$this->summary}</title>
  <titleabbrev>$idName</titleabbrev>

  <partintro>
   <section id='$idName.intro'>
    &reftitle.intro;
    <para>
{$this->description}
    </para>
   </section>

   <section xml:id='$idName.requirements'>
    &reftitle.required;
    <para>

    </para>
   </section>

   &reference.$idName.configure;
   &reference.extname.ini;

   <section id='$idName.resources'>
    &reftitle.resources;
");

        if (empty($this->resources)) {
            $fp->puts("   &no.resource;\n");
        } else {
            foreach ($this->resources as $resource) {
                $fp->puts($resource->docEntry($idName));
            }
        }

        $fp->puts(
"   </section>

   &reference.extname.constants;
  </partintro>

&reference.$idName.functions;

 </reference>
");

        $fp->puts($this->docEditorSettings());

        $fp->close();

        //
        // constants.xml
        //

        $entities->puts("<!ENTITY reference.$idName.constants SYSTEM './$idName/constants.xml'>\n");

        $fp = new CodeGen_Tools_FileReplacer("$docdir/$idName/constants.xml");

        $fp->puts(
"<?xml version='1.0' encoding='iso-8859-1'?>
<!-- ".'$'."Revision: 1.1 $ -->
");

        $fp->puts("<section id='$idName.constants' xmlns='http://docbook.org/ns/docbook' xmlns:xlink='http://www.w3.org/1999/xlink'>\n");

        $fp->puts(" &reftitle.constants;\n");
        $fp->puts(" &extension.constants;\n");

        $fp->puts(" <para>\n");

        if (empty($this->constants)) {
            $fp->puts("    &no.constants;\n");
        } else {
            $const_groups = array();
            foreach ($this->constants as $constant) {
                $const_groups[$constant->getGroup()][] = $constant;
            }
            foreach ($const_groups as $group => $constants) {
                if ($group == "default") {
                    $group = $idName;
                }
                $fp->puts(CodeGen_PECL_Element_Constant::docHeader($group));
                foreach ($constants as $constant) {
                    $fp->puts($constant->docEntry($group));
                }
                $fp->puts(CodeGen_PECL_Element_Constant::docFooter());
            }
        }

        // TODO: 2nd half missing, see http://doc.php.net/php/de/dochowto/c578.php

        $fp->puts(" </para>\n");
        $fp->puts("</section>\n");

        $fp->puts($this->docEditorSettings());
        $fp->close();

        //
        // ini.xml
        //

        $entities->puts("<!ENTITY reference.$idName.ini SYSTEM './$idName/ini.xml'>\n");

        $fp = new CodeGen_Tools_FileReplacer("$docdir/$idName/ini.xml");

        $fp->puts(
"<?xml version='1.0' encoding='iso-8859-1'?>
<!-- ".'$'."Revision: 1.1 $ -->
");

        $fp->puts("<section id='$idName.configuration' xmlns='http://docbook.org/ns/docbook' xmlns:xlink='http://www.w3.org/1999/xlink'>\n");

        $fp->puts(" &reftitle.runtime;\n");
        $fp->puts(" &extension.runtime;\n");

        $fp->puts(" <para>\n");

        if (empty($this->phpini)) {
            $fp->puts("    &no.config;\n");
        } else {
            $fp->puts(CodeGen_PECL_Element_Ini::docHeader($this->name));
            foreach ($this->phpini as $phpini) {
                $fp->puts($phpini->docEntry($idName));
            }
            $fp->puts(CodeGen_PECL_Element_Ini::docFooter());
        }

        $fp->puts(" </para>\n");
        $fp->puts("</section>\n");

        $fp->puts($this->docEditorSettings());
        $fp->close();

        //
        // configure.xml
        //

        // configure options and dependencies have their own file
        $entities->puts("<!ENTITY reference.$idName.configure SYSTEM './$idName/configure.xml'>\n");

        $fp = new CodeGen_Tools_FileReplacer("$docdir/$idName/configure.xml");

        $fp->puts(
"<?xml version='1.0' encoding='iso-8859-1'?>
<!-- ".'$'."Revision: 1.1 $ -->
");

        $fp->puts("\n   <section id='$idName.requirements'>\n    &reftitle.required;\n");

        // TODO headers and libs are now "hidden" in $with

        if (empty($this->libs) && empty($this->headers)) {
            $fp->puts("    &no.requirement;\n");
        } else {
            // TODO allow custom text
            if (isset($this->libs)) {
                $libs = array();
                foreach ($this->libs as $lib) {
                    $libs[] = $lib->getName();
                }
                $ies = count($libs)>1 ? "ies" :"y";
                $fp->puts("<para>This extension requires the following librar$ies: ".join(",", $libs)."</para>\n");
            }
            if (isset($this->headers)) {
                $headers = array();
                foreach ($this->headers as $header) {
                    $headers[] = $header->getName();
                }
                $s = count($headers)>1 ? "s" : "";
                $fp->puts("<para>This extension requires the following header$s: ".join(",", $headers)."</para>\n");
            }
        }
        $fp->puts("\n   </section>\n\n");

        $fp->puts("\n   <section id='$idName.install'>\n    &reftitle.install;\n");
        if (empty($this->with)) {
            $fp->puts("    &no.install;\n");
        } else {
            foreach ($this->with as $with) {
                if (isset($with->summary)) {
                    if (strstr($with->summary, "<para>")) {
                        $fp->puts($with->summary);
                    } else {
                        $fp->puts("    <para>\n".rtrim($with->summary)."\n    </para>\n");
                    }
                } else {
                    $fp->puts("    <para>Requires <literal>".$with->getName()."</literal></para>\n");
                }
            }
        }
        $fp->puts("\n   </section>\n\n");

        $fp->puts($this->docEditorSettings());
        $fp->close();

        //

        $function_entities = array();
        @mkdir("$docdir/$idName/functions");
        foreach ($this->functions as $name => $function) {
            $functionId = strtolower(str_replace("_", "-", $name));
            $filepath   = "$idName/functions/$functionId.xml";

            $entity = "reference.$idName.functions.$functionId";

            $function_entities[] = $entity;
            $entities->puts("<!ENTITY $entity SYSTEM './$filepath'>\n");

            $funcfile = new CodeGen_Tools_FileReplacer("$docdir$filepath");
            $funcfile->puts($function->docEntry($idName));
            $funcfile->puts($this->docEditorSettings(4));
            $funcfile->close();
        }

        $entities->puts("<!ENTITY reference.$idName.functions SYSTEM './functions.xml'>\n");
        $entities->close();

        $functionsXml = new CodeGen_Tools_FileReplacer($docdir."/functions.xml");
        sort($function_entities);
        foreach ($function_entities as $entity) {
            $functionsXml->puts(" &$entity;\n");
        }
        $functionsXml->close();
    }

    // }}}

    // {{{   extension entry
    /**
     * Create the module entry code for this extension
     *
     * @access private
     * @return string  zend_module_entry code fragment
     */
    function generateExtensionEntry()
    {
        $name   = $this->name;
        $upname = strtoupper($this->name);
        $code   = "";

        if (empty($this->otherExtensions)) {
            $moduleHeader = "    STANDARD_MODULE_HEADER,";
        } else {
            $code.= CodeGen_PECL_Dependency_Extension::cCodeHeader($this);
            foreach ($this->otherExtensions as $ext) {
                $code.= $ext->cCode($this);
            }
            $code.= CodeGen_PECL_Dependency_Extension::cCodeFooter($this);

            $moduleHeader =
"#if ZEND_EXTENSION_API_NO >= 220050617
        STANDARD_MODULE_HEADER_EX, NULL,
        {$this->name}_deps,
#else
        STANDARD_MODULE_HEADER,
#endif
";
        }

        $code.= "
/* {{{ {$name}_module_entry
 */
zend_module_entry {$name}_module_entry = {
$moduleHeader
    \"$name\",
    {$name}_functions,
    PHP_MINIT($name),     /* Replace with NULL if there is nothing to do at php startup   */
    PHP_MSHUTDOWN($name), /* Replace with NULL if there is nothing to do at php shutdown  */
    PHP_RINIT($name),     /* Replace with NULL if there is nothing to do at request start */
    PHP_RSHUTDOWN($name), /* Replace with NULL if there is nothing to do at request end   */
    PHP_MINFO($name),
    PHP_".$upname."_VERSION,
    STANDARD_MODULE_PROPERTIES
};
/* }}} */

";

        $code .= "#ifdef COMPILE_DL_$upname\n";
        if ($this->language == "cpp") {
            $code .= "extern \"C\" {\n";
        }
        $code .= "ZEND_GET_MODULE($name)\n";
        if ($this->language == "cpp") {
            $code .= "} // extern \"C\"\n";
        }
        $code .= "#endif\n\n";

        return $code;
    }

    // }}}

    // {{{ globals and ini
    /**
     * Create the module globals c code fragment
     *
     * @access private
     * @return string  module globals code fragment
     */

    function generateGlobalsC()
    {
        if (empty($this->globals)) return "";

        $code  = "\n/* {{{ globals and ini entries */\n";
        $code .= "ZEND_DECLARE_MODULE_GLOBALS({$this->name})\n\n";

        if (!empty($this->phpini)) {
            $code .= CodeGen_PECL_Element_Ini::cCodeHeader($this->name);
            foreach ($this->phpini as $phpini) {
                $code .= $phpini->cCode($this->name);
            }
            $code .= CodeGen_PECL_Element_Ini::cCodeFooter($this->name);
        }

        if (!empty($this->globals)) {
            $code .= CodeGen_PECL_Element_Global::cCodeHeader($this->name);
            foreach ($this->globals as $global) {
                $code .= $global->cCode($this->name);
            }
            $code .= CodeGen_PECL_Element_Global::cCodeFooter($this->name);
        }

        $code .= "/* }}} */\n\n";
        return $code;
    }

    /**
     * Create the module globals c header file fragment
     *
     * @access private
     * @return string  module globals code fragment
     */
    function generateGlobalsH()
    {
        if (empty($this->globals)) return "";

        $code = CodeGen_PECL_Element_Global::hCodeHeader($this->name);
        foreach ($this->globals as $global) {
            $code .= $global->hCode($this->name);
        }
        $code .= CodeGen_PECL_Element_Global::hCodeFooter($this->name);

        return $code;
    }

    // }}}

    // {{{
    /**
     * Create global function registration
     *
     * @access private
     * @return string  function registration code fragments
     */
    function generateFunctionRegistrations()
    {
        $code  = "/* {{{ {$this->name}_functions[] */\n";
        $code .= "function_entry {$this->name}_functions[] = {\n";
        foreach ($this->functions as $function) {
            $code.= $function->functionEntry();
        }
        foreach ($this->classes as $class) {
            $code.= $class->functionAliasEntries();
        }
        $code .=  "    { NULL, NULL, NULL }\n";
        $code .=  "};\n/* }}} */\n\n";

        return $code;
    }
    // }}}

    // {{{
    /**
     * Create global class registration code and functions
     *
     * @access private
     * @return string  class registration code fragments
     */
    function generateClassRegistrations()
    {
        if (empty($this->classes)) return "";

        $code = "/* {{{ Class definitions */\n\n";

        foreach ($this->classes as $class) {
            $code .= $class->globalCode($this);
        }

        $code .= "/* }}} Class definitions*/\n\n";

        return $code;
    }
    // }}}

    // {{{
    /**
     * Create global interface registration code
     *
     * @access private
     * @return string  interface registration code fragments
     */
    function generateInterfaceRegistrations()
    {
        if (empty($this->interfaces)) return "";

        $code = "/* {{{ Interface definitions */\n\n";

        foreach ($this->interfaces as $interface) {
            $code .= $interface->globalCode($this);
        }

        $code .= "/* }}} Interface definitions*/\n\n";

        return $code;
    }
    // }}}

    // {{{ license and authoers
    /**
     * Set license
     *
     * @access public
     * @param  object
     */
    function setLicense($license)
    {
        if (preg_match("|^GPL|", $license->getShortName())) {
            return PEAR::raiseError("The ".$license->getShortName().
                                    "is not a valid choice for PHP extensions due to license incompatibilities");
        }

        $this->license = $license;

        return true;
    }

    /**
     * Create the license part of the source file header comment
     *
     * @access private
     * @return string  code fragment
     */
    function getLicenseComment()
    {
        $code = "/*\n";
        $code.= "   +----------------------------------------------------------------------+\n";

        if (is_object($this->license)) {
            $code.= $this->license->getComment();
        } else {
            $code.= sprintf("   | unknown license: %-52s |\n", $this->license);
        }

        $code.= "   +----------------------------------------------------------------------+\n";

        foreach ($this->authors as $author) {
            $code.= $author->comment();
        }

        $code.= "   +----------------------------------------------------------------------+\n";
        $code.= "*/\n\n";

        $code.= "/* $ Id: $ */ \n\n";

        return $code;
    }

    // }}}

    /**
     * Set pear installer channel
     *
     * @access public
     * @param  string
     */
    function setChannel($channel)
    {
        if (! preg_match('/^[a-z\-_\.]+$/i', $channel)) {
            return PEAR::raiseError("'$channel' is not a valid pear installer channel name");
        }

        $this->channel = $channel;
    }

    // {{{ header file

    /**
     * Write the complete C header file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeHeaderFile()
    {
        $this->addPackageFile('header', "php_{$this->name}.h");

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/php_{$this->name}.h");

        $upname = strtoupper($this->name);

        echo $this->getLicenseComment();
        echo "#ifndef PHP_{$upname}_H\n";
        echo "#define PHP_{$upname}_H\n\n";

        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }

        echo "#ifdef  __cplusplus\n";
        echo "extern \"C\" {\n";
        echo "#endif\n";

        echo '
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>

#ifdef HAVE_'.$upname.'
';

       echo '#define PHP_'.$upname.'_VERSION "'.$this->release->getVersion().'"'."\n\n";

       echo '
#include <php_ini.h>
#include <SAPI.h>
#include <ext/standard/info.h>
#include <Zend/zend_extensions.h>
';

        echo "#ifdef  __cplusplus\n";
        echo "} // extern \"C\" \n";
        echo "#endif\n";

        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }

        foreach ($this->with as $with) {
            foreach ($with->getHeaders() as $header) {
                echo $header->hCode(false);
            }
        }

        if (isset($this->code["header"]["top"])) {
            foreach ($this->code["header"]["top"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }

        echo "#ifdef  __cplusplus\n";
        echo "extern \"C\" {\n";
        echo "#endif\n";

        echo "
extern zend_module_entry {$this->name}_module_entry;
#define phpext_{$this->name}_ptr &{$this->name}_module_entry

#ifdef PHP_WIN32
#define PHP_{$upname}_API __declspec(dllexport)
#else
#define PHP_{$upname}_API
#endif

PHP_MINIT_FUNCTION({$this->name});
PHP_MSHUTDOWN_FUNCTION({$this->name});
PHP_RINIT_FUNCTION({$this->name});
PHP_RSHUTDOWN_FUNCTION({$this->name});
PHP_MINFO_FUNCTION({$this->name});

#ifdef ZTS
#include \"TSRM.h\"
#endif

#define FREE_RESOURCE(resource) zend_list_delete(Z_LVAL_P(resource))

#define PROP_GET_LONG(name)    Z_LVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_LONG(name, l) zend_update_property_long(_this_ce, _this_zval, #name, strlen(#name), l TSRMLS_CC)

#define PROP_GET_DOUBLE(name)    Z_DVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_DOUBLE(name, d) zend_update_property_double(_this_ce, _this_zval, #name, strlen(#name), d TSRMLS_CC)

#define PROP_GET_STRING(name)    Z_STRVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_GET_STRLEN(name)    Z_STRLEN_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_STRING(name, s) zend_update_property_string(_this_ce, _this_zval, #name, strlen(#name), s TSRMLS_CC)
#define PROP_SET_STRINGL(name, s, l) zend_update_property_stringl(_this_ce, _this_zval, #name, strlen(#name), s, l TSRMLS_CC)

";

        echo $this->generateGlobalsH();

        echo "\n";

        foreach ($this->functions as $name => $function) {
            echo $function->hCode($this);
        }

        foreach ($this->classes as $name => $class) {
            echo $class->hCode($this);
        }

        foreach ($this->interfaces as $name => $interface) {
            echo $interface->hCode($this);
        }

        foreach ($this->streams as $name => $stream) {
            echo $this->codegen->block($stream->hCode());
        }

        echo "#ifdef  __cplusplus\n";
        echo "} // extern \"C\" \n";
        echo "#endif\n";
        echo "\n";

        // write #defines for <constant>s
        $defines = "";
        foreach ($this->constants as $constant) {
            $defines.= $constant->hCode($this);
        }
        if ($defines !== "") {
            echo "/* mirrored PHP Constants */\n";
            echo $defines;
            echo "\n";
        }

        // add bottom header snippets
        if (isset($this->code["header"]["bottom"])) {
            echo "/* 'bottom' header snippets*/\n";
            foreach ($this->code["header"]["bottom"] as $code) {
                echo $this->codegen->block($code, 0);
            }
            echo "\n";
        }

        echo "#endif /* PHP_HAVE_{$upname} */\n\n";
        echo "#endif /* PHP_{$upname}_H */\n\n";

        echo $this->cCodeEditorSettings();

        return $file->write();
    }

    // }}}

    // {{{ internal functions

    /**
     * Create code for the internal functions like MINIT etc ...
     *
     * @access private
     * @return string  code snippet
     */

    function internalFunctionsC()
    {
        $need_block = false;

        $code = "
/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION({$this->name})
{
";

        if (count($this->globals)) {
            $code      .= "    ZEND_INIT_MODULE_GLOBALS({$this->name}, php_{$this->name}_init_globals, php_{$this->name}_shutdown_globals)\n";
            $need_block = true;
        }

        if (count($this->phpini)) {
            $code      .= "    REGISTER_INI_ENTRIES();\n";
            $need_block = true;
        }

        foreach ($this->logos as $logo) {
            $code      .= $this->codegen->block($logo->minitCode());
            $need_block = true;
        }

        if (count($this->constants)) {
            foreach ($this->constants as $constant) {
                $code .= $this->codegen->block($constant->cCode($this->name));
            }
            $need_block = true;
        }

        if (count($this->resources)) {
            foreach ($this->resources as $resource) {
                $code .= $this->codegen->block($resource->minitCode());
            }
            $need_block = true;
        }

        if (count($this->interfaces)) {
            foreach ($this->interfaces as $interface) {
                $code .= $this->codegen->block($interface->minitCode($this));
            }
            $need_block = true;
        }

        if (count($this->classes)) {
            foreach ($this->classes as $class) {
                $code .= $this->codegen->block($class->minitCode($this));
            }
            $need_block = true;
        }

        if (count($this->streams)) {
            foreach ($this->streams as $stream) {
                $code .= $this->codegen->block($stream->minitCode($this));
            }
            $need_block = true;
        }

        if (isset($this->internalFunctions['MINIT'])) {
            $code .= $this->codegen->varblock($this->internalFunctions['MINIT']->getCode());
        } else {
            $code .="\n    /* add your stuff here */\n";
        }
        $code .= "
    return SUCCESS;
}
/* }}} */

";

        $code .= "
/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION({$this->name})
{
";

        if (count($this->phpini)) {
            $code      .= "    UNREGISTER_INI_ENTRIES();\n";
            $need_block = true;
        }

        // TODO: need to destruct globals here if in ZTS mode!!111

        if (count($this->logos)) {
            foreach ($this->logos as $logo) {
                $code .= $this->codegen->block($logo->mshutdownCode());
            }
            $need_block = true;
        }

        if (isset($this->internalFunctions['MSHUTDOWN'])) {
            $code .= $this->codegen->varblock($this->internalFunctions['MSHUTDOWN']->getCode());
        } else {
            $code .="\n    /* add your stuff here */\n";
        }

        $code .= "
    return SUCCESS;
}
/* }}} */

";

        $code .= "
/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION({$this->name})
{
";

        if (isset($this->internalFunctions['RINIT'])) {
            $code .= $this->codegen->block($this->internalFunctions['RINIT']->getCode());
        } else {
            $code .= "    /* add your stuff here */\n";
        }

        $code .= "
    return SUCCESS;
}
/* }}} */

";

        $code .= "
/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION({$this->name})
{
";

        if (isset($this->internalFunctions['RSHUTDOWN'])) {
            $code .= $this->codegen->block($this->internalFunctions['RSHUTDOWN']->getCode());
        } else {
            $code .= "    /* add your stuff here */\n";
        }

        $code .= "
    return SUCCESS;
}
/* }}} */

";

        $code .= "
/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION({$this->name})
{
";

        if (!empty($this->logos)) {
            $code.= "    if (!sapi_module.phpinfo_as_text) {\n";
            foreach ($this->logos as $logo) {
                $code.= $logo->phpinfoCode($this->name);
            }
            echo "    }\n";
        }

        if (!empty($this->summary)) {
            $summary = strtr(trim($this->summary), array('"'=>'\\"'));
            $code .= "    php_printf(\"$summary\\n\");\n";
        }

        $code.= "    php_info_print_table_start();\n";

        if (!empty($this->release)) {
            $code .= $this->release->phpinfoCode($this->name);
        }

        if (count($this->authors)) {
            $code.= '    php_info_print_table_row(2, "Authors", "';

            foreach ($this->authors as $author) {
                $code.= $author->phpinfoCode($this->name).'\\n';
            }

            $code.= "\");\n";
        }

        $code.=
"    php_info_print_table_end();
";

        // TODO move this decision up?
        if (isset($this->internalFunctions['MINFO'])) {
            $code .= $this->codegen->varblock($this->internalFunctions['MINFO']->getCode());
        } else {
            $code .= "    /* add your stuff here */\n";
        }

        if (count($this->phpini)) {
            $code .= "\n    DISPLAY_INI_ENTRIES();";
        }
        $code .= "
}
/* }}} */

";

        return $code;
    }

    // }}}

    // {{{ public functions
    /**
     * Create code for the exported PHP functions
     *
     * @access private
     * @return string  code snippet
     */
    function publicFunctionsC()
    {
        $code = "";

        foreach ($this->functions as $function) {
            $code .= $function->cCode($this);
        }

        return $code;
    }

    // }}}

    // {{{ code file

    /**
     * Write the complete C code file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeCodeFile()
    {
        $filename = "{$this->name}.{$this->language}";  // todo extension logic

        $upname = strtoupper($this->name);

        $this->addPackageFile('code', $filename);

        $file = new CodeGen_Tools_Outbuf($this->dirpath.'/'.$filename, CodeGen_Tools_Outbuf::OB_TABIFY);

        echo $this->getLicenseComment();

        echo "#include \"php_{$this->name}.h\"\n\n";

        echo "#if HAVE_$upname\n\n";

        if (isset($this->code["code"]["top"])) {
            foreach ($this->code["code"]["top"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }

        if (!empty($this->logos)) {
            echo CodeGen_PECL_Element_Logo::cCodeHeader($this->name);
            foreach ($this->logos as $logo) {
                echo $logo->cCode($this->name);
            }
            echo CodeGen_PECL_Element_Logo::cCodeFooter($this->name);
        }

        if (!empty($this->resources)) {
            echo CodeGen_PECL_Element_Resource::cCodeHeader($this->name);
            foreach ($this->resources as $resource) {
                echo $resource->cCode($this);
            }
            echo CodeGen_PECL_Element_Resource::cCodeFooter($this->name);
        }

        echo $this->generateInterfaceRegistrations();

        echo $this->generateClassRegistrations();

        echo $this->generateFunctionRegistrations();

        echo $this->generateExtensionEntry();

        echo $this->generateGlobalsC();

        echo $this->internalFunctionsC();

        echo $this->publicFunctionsC();

        if (isset($this->code["code"]["bottom"])) {
            foreach ($this->code["code"]["bottom"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }

        echo "#endif /* HAVE_$upname */\n\n";

        echo $this->cCodeEditorSettings();

        return $file->write();
    }

    // }}}

    // {{{ config.m4 file

    /**
     * Write config.m4 file for autoconf
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeConfigM4()
    {
        $upname = strtoupper($this->name);

        $this->addPackageFile("conf", "config.m4");

        $file = new CodeGen_Tools_Outbuf($this->dirpath."/config.m4", CodeGen_Tools_Outbuf::OB_TABIFY);

        echo
'dnl
dnl $ Id: $
dnl
';

        if (isset($this->with[$this->name])) {
            $with = $this->with[$this->name];
            echo "\n".$with->m4Line()."\n";
        } else {
            echo "
PHP_ARG_ENABLE({$this->name}, whether to enable {$this->name} functions,
[  --enable-{$this->name}         Enable {$this->name} support])
";
        }

        echo "\n";

        echo "if test \"\$PHP_$upname\" != \"no\"; then\n";

        if ($this->language === "cpp") {
            echo "  PHP_REQUIRE_CXX\n";
            echo "  AC_LANG_CPLUSPLUS\n";
            echo "  PHP_ADD_LIBRARY(stdc++,,{$upname}_SHARED_LIBADD)\n";
        }

        foreach ($this->configfragments['top'] as $fragment) {
            echo "$fragment\n";
        }

        foreach ($this->with as $with) {
            echo $with->configm4($this);
        }

        $pathes = array();
        foreach ($this->headers as $header) {
            $pathes[$header->getPath()] = true; // TODO WTF???
        }

        foreach (array_keys($pathes) as $path) {
            echo "  PHP_ADD_INCLUDE(\$PHP_{$upname}_DIR/$path)\n";
        }

        echo "  export OLD_CPPFLAGS=\"\$CPPFLAGS\"\n";
        echo "  export CPPFLAGS=\"\$CPPFLAGS \$INCLUDES -DHAVE_".strtoupper($this->name)."\"\n";

        echo "
  AC_MSG_CHECKING(PHP version)
  AC_TRY_COMPILE([#include <php_version.h>], [
#if PHP_VERSION_ID < ".$this->minPhpVersionId()."
#error  this extension requires at least PHP version ".$this->minPhpVersion()."
#endif
],
[AC_MSG_RESULT(ok)],
[AC_MSG_ERROR([need at least PHP ".$this->minPhpVersion()."])])

";

        if (count($this->headers)) {
            if (!isset($this->with[$this->name])) {
                $this->terminate("global headers not bound to a --with option found and no --with option by the default name");
            }

            foreach ($this->headers as $header) {
                echo $header->configm4($this->name, $this->name);
            }
        }

        foreach ($this->resources as $resource) {
            echo $resource->configm4($this->name);
        }

        echo "  export CPPFLAGS=\"\$OLD_CPPFLAGS\"\n";

        if (count($this->libs)) {
            if (!isset($this->with[$this->name])) {
                $this->terminate("global libs not bound to a --with option found and no --with option by the default name");
            }
            foreach ($this->libs as $lib) {
                echo $lib->configm4($this->name, $this->name);
            }
        }

        echo "\n";

        echo "
  PHP_SUBST({$upname}_SHARED_LIBADD)
  AC_DEFINE(HAVE_$upname, 1, [ ])
";

        foreach ($this->defines as $define) {
          echo "  AC_DEFINE([$define[name]], [$define[value]], [$define[comment]])\n";
        }

        echo "
  PHP_NEW_EXTENSION({$this->name}, ".join(" ", array_keys($this->packageFiles['code']))." , \$ext_shared)
";

        if (count($this->makefragments)) {
            echo "  PHP_ADD_MAKEFILE_FRAGMENT\n";

            $frag = new CodeGen_Tools_FileReplacer($this->dirpath."/Makefile.frag");
            foreach ($this->makefragments as $block) {
                $frag->puts(CodeGen_Tools_IndentC::tabify("\n$block\n"));
            }
            $frag->close();
        }

        foreach ($this->configfragments['bottom'] as $fragment) {
            echo "$fragment\n";
        }

        echo
"
fi

";

        return $file->write();
    }

    // }}}

    // {{{ config.w32 file

    /**
     * Write config.w32 file for new windows build system
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeConfigW32()
    {
        // TODO fragments
        $upname = strtoupper($this->name);

        $this->addPackageFile("conf", "config.w32");

        $file = new CodeGen_Tools_Outbuf($this->dirpath."/config.w32",
                                         CodeGen_Tools_Outbuf::OB_UNTABIFY
                                         | CodeGen_Tools_Outbuf::OB_DOSIFY);

        echo
'// $ Id: $
// vim:ft=javascript
';

        if (isset($this->with[$this->name])) {
            echo "
ARG_WITH('{$this->name}', '{$this->summary}', 'no');

";
        } else {
            echo "
ARG_ENABLE('{$this->name}' , '{$this->summary}', 'no');
";
        }

        echo "if (PHP_$upname == \"yes\") {\n";

        // add libraries from <deps> section
        foreach ($this->libs as $lib) {
            echo $lib->configw32($this->name, $this->name);
        }

        foreach ($this->headers as $header) {
            echo $header->configw32($this->name, $this->name);
        }

        echo "  EXTENSION(\"{$this->name}\", \"".join(" ", array_keys($this->packageFiles['code']))."\");\n";

        echo "  AC_DEFINE(\"HAVE_$upname\", 1, \"{$this->name} support\");\n";

        foreach ($this->defines as $define) {
            echo "  AC_DEFINE(\"$define[name]\", $define[value], \"$define[comment]\")\n";
        }

        echo "}\n";

        $file->write();
    }

    // }}}

    // {{{ M$ dev studio project file

    /**
     * Write project file for VisualStudio V6
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeMsDevStudioDsp()
    {
        $filename = $this->name.".dsp";
        $this->addPackageFile("conf", $filename);
        $file = new CodeGen_Tools_Outbuf($this->dirpath.'/'.$filename,
                                         CodeGen_Tools_Outbuf::OB_UNTABIFY
                                         | CodeGen_Tools_Outbuf::OB_DOSIFY);

        // these system libraries are always needed?
        // (list taken from sample *.dsp files in php ext tree...)
        $winlibs = "kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib ";
        $winlibs.= "shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib";

        // add libraries from <deps> section
        if (count($this->libs)) {
            foreach ($this->libs as $lib) {
                if (!$lib->testPlatform("windows")) {
                    continue;
                }
                $winlibs .= " ".$lib->getName().".lib";
            }
        }

        $defines = '/D HAVE_'.strtoupper($this->name).'=1 ';
        foreach ($this->defines as $define) {
            $defines = '/D "'.$define['name'].'='.$define['value'].'" "';
        }

        echo
'# Microsoft Developer Studio Project File - Name="'.$this->name.'" - Package Owner=<4>
# Microsoft Developer Studio Generated Build File, Format Version 6.00
# ** DO NOT EDIT **

# TARGTYPE "Win32 (x86) Dynamic-Link Library" 0x0102

CFG='.$this->name.' - Win32 Debug_TS
!MESSAGE This is not a valid makefile. To build this project using NMAKE,
!MESSAGE use the Export Makefile command and run
!MESSAGE
!MESSAGE NMAKE /f "'.$this->name.'.mak".
!MESSAGE
!MESSAGE You can specify a configuration when running NMAKE
!MESSAGE by defining the macro CFG on the command line. For example:
!MESSAGE
!MESSAGE NMAKE /f "'.$this->name.'.mak" CFG="'.$this->name.' - Win32 Debug_TS"
!MESSAGE
!MESSAGE Possible choices for configuration are:
!MESSAGE
!MESSAGE "'.$this->name.' - Win32 Release_TS" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "'.$this->name.' - Win32 Debug_TS" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE

# Begin Project
# PROP AllowPerConfigDependencies 0
# PROP Scc_ProjName ""
# PROP Scc_LocalPath ""
CPP=cl.exe
MTL=midl.exe
RSC=rc.exe

!IF  "$(CFG)" == "'.$this->name.' - Win32 Release_TS"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "Release_TS"
# PROP BASE Intermediate_Dir "Release_TS"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "Release_TS"
# PROP Intermediate_Dir "Release_TS"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MT /W3 /GX /O2 /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "'.strtoupper($this->name).'_EXPORTS" /YX /FD /c
# ADD CPP /nologo /MT /W3 /GX /O2 /I "..\.." /I "..\..\Zend" /I "..\..\TSRM" /I "..\..\main" /D "WIN32" /D "PHP_EXPORTS" /D "COMPILE_DL_'.strtoupper($this->name).'" /D ZTS=1 '.$defines.' /D ZEND_DEBUG=0 /D "NDEBUG" /D "_WINDOWS" /D "ZEND_WIN32" /D "PHP_WIN32" /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x407 /d "NDEBUG"
# ADD RSC /l 0x407 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 '.$winlibs.' /nologo /dll /machine:I386
# ADD LINK32 php4ts.lib '.$winlibs.' /nologo /dll /machine:I386 /out:"..\..\Release_TS\php_'.$this->name.'.dll" /libpath:"..\..\Release_TS" /libpath:"..\..\Release_TS_Inline"

!ELSEIF  "$(CFG)" == "'.$this->name.' - Win32 Debug_TS"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "Debug_TS"
# PROP BASE Intermediate_Dir "Debug_TS"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "Debug_TS"
# PROP Intermediate_Dir "Debug_TS"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MTd /W3 /Gm /GX /ZI /Od /D "WIN32" /D "_DEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "'.strtoupper($this->name).'_EXPORTS" /YX /FD /GZ  /c
# ADD CPP /nologo /MTd /W3 /Gm /GX /ZI /Od /I "..\.." /I "..\..\Zend" /I "..\..\TSRM" /I "..\..\main" /D ZEND_DEBUG=1 /D "WIN32" /D "_DEBUG" /D "_WINDOWS" /D "PHP_EXPORTS" /D "COMPILE_DL_'.strtoupper($this->name).'" /D ZTS=1 /D "ZEND_WIN32" /D "PHP_WIN32" '.$defines.' /YX /FD /GZ  /c
# ADD BASE MTL /nologo /D "_DEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "_DEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x407 /d "_DEBUG"
# ADD RSC /l 0x407 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 '.$winlibs.' /nologo /dll /debug /machine:I386 /pdbtype:sept
# ADD LINK32 php4ts_debug.lib '.$winlibs.' /nologo /dll /debug /machine:I386 /out:"..\..\Debug_TS\php_'.$this->name.'.dll" /pdbtype:sept /libpath:"..\..\Debug_TS"

!ENDIF

# Begin Target

# Name "'.$this->name.' - Win32 Release_TS"
# Name "'.$this->name.' - Win32 Debug_TS"
';

        echo '
# Begin Group "Source Files"

# PROP Default_Filter "cpp;c;cxx;rc;def;r;odl;idl;hpj;bat"
';

        foreach ($this->packageFiles['code'] as $basename => $filepath) {
             $filename = "./$basename";

             echo "
# Begin Source File

SOURCE=$filename
# End Source File
";
        }

        echo '
# End Group
';

        echo '
# Begin Group "Header Files"

# PROP Default_Filter "h;hpp;hxx;hm;inl"
';

        foreach ($this->packageFiles['header'] as $filename) {
            if ($filename{0}!='/' && $filename{0}!='.') {
                $filename = "./$filename";
            }
            $filename = str_replace("/","\\",$filename);

            echo "
# Begin Source File

SOURCE=$filename
# End Source File
";
        }

        echo
'# End Group
# End Target
# End Project
';

        return $file->write();
    }

// }}}

    /**
     * Write authors to the CREDITS file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeCredits()
    {
        if (count($this->authors)) {
            $this->addPackageFile("doc", "CREDITS");
            $fp = new CodeGen_Tools_FileReplacer($this->dirpath."/CREDITS");
            $fp->puts("{$this->name}\n");
            $names = array();
            foreach ($this->authors as $author) {
                $names[] = $author->getName();
            }
            $fp->puts(join(", ", $names) . "\n");
            $fp->close();
        }
    }

    /**
    * Write EXPERIMENTAL file for non-stable extensions
    *
    * @access private
    * @param  string  directory to write to
    */
    function writeExperimental()
    {
        if (($this->release) && $this->release->getState() === 'stable') {
            return;
        }

        $this->addPackageFile("doc", "EXPERIMENTAL");
        $fp = new CodeGen_Tools_FileReplacer($this->dirpath."/EXPERIMENTAL");
        $fp->puts(
"this extension is experimental,
its functions may change their names
or move to extension all together
so do not rely to much on them
you have been warned!
");
        $fp->close();
    }

    /**
    * Write file list for package.xml (both version 1.0 and 2.0)
    *
    * @return string
    */
    protected function packageXmlFileList()
    {
        $code = "";

        $code.= "    <dir name=\"/\">\n";
        if (@is_array($this->packageFiles['doc'])) {
            foreach ($this->packageFiles['doc'] as $file) {
                $code.= "      <file role='doc' name='$file'/>\n";
            }
        }

        foreach (array("conf", "code", "header") as $type) {
            foreach ($this->packageFiles[$type] as $basename => $filepath) {
                $code.= "      <file role='src' name='$basename'/>\n";
            }
        }

        if (!empty($this->packageFiles['test'])) {
            $code.= "      <dir name=\"tests\">\n";
            foreach ($this->packageFiles['test'] as $basename => $filepath) {
                $code.= "        <file role='test' name='$basename'/>\n";
            }
            $code.= "      </dir>\n";
        }

        $code.= "    </dir>\n";

        return $code;
      }

    /**
     * Write PEAR/PECL package.xml file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writePackageXml()
    {
        $outfile = new CodeGen_Tools_Outbuf($this->dirpath."/package.xml");

        echo
"<?xml version=\"1.0\"?>
<!DOCTYPE package SYSTEM \"http://pear.php.net/dtd/package-1.0\">
<package>

  <name>{$this->name}</name>
";

        if (isset($this->summary)) {
            echo "  <summary>{$this->summary}</summary>\n";
        }

        if (isset($this->description)) {
            echo "  <description>\n".rtrim($this->description)."\n  </description>\n";
        }

        if ($this->license) {
            echo "\n  <license>".$this->license->getShortName()."</license>\n";
        }

        if (count($this->with)) {
            echo "\n  <configureoptions>\n";
            foreach ($this->with as $with) {
                $configOption = "with-".$with->getName();
                echo "   <configureoption name=\"{$configOption}\" default=\"autodetect\" prompt=\"".$with->getName()." installation directory?\" />\n";
            }
            echo "  </configureoptions>\n";
        }

        if (count($this->authors)) {
            echo "\n  <maintainers>\n";
            foreach ($this->authors as $author) {
                echo $author->packageXml();
            }
            echo "  </maintainers>\n";
        }

        if (isset($this->release)) {
            echo $this->release->packageXml();
        }

        echo "  <changelog>\n";
        echo $this->changelog."\n"; // TODO indent
        echo "  </changelog>\n";

        echo "  <deps>\n";
        echo "    <dep type=\"php\" rel=\"ge\" version=\"".$this->minPhpVersion()."\"/>\n";
        echo $this->platform->packageXML();
        foreach ($this->otherExtensions as $ext) {
            echo $ext->packageXML();
        }
        echo "  </deps>\n";

        echo "\n  <filelist>\n";
        echo $this->packageXmlFileList();
        echo "  </filelist>\n";

        echo "</package>\n";

        return $outfile->write();
    }

    // }}}

    /**
     * Write PEAR/PECL package2.xml file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writePackageXml2()
    {
        $outfile = new CodeGen_Tools_Outbuf($this->dirpath."/package2.xml");

        echo
'<?xml version="1.0"?>
<package version="2.0" xmlns="http://pear.php.net/dtd/package-2.0"
    xmlns:tasks="http://pear.php.net/dtd/tasks-1.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0
http://pear.php.net/dtd/tasks-1.0.xsd
http://pear.php.net/dtd/package-2.0
http://pear.php.net/dtd/package-2.0.xsd">

';

        echo "  <name>{$this->name}</name>\n";
        echo "  <channel>{$this->channel}</channel>\n\n";

        if (isset($this->summary)) {
            echo "  <summary>{$this->summary}</summary>\n\n";
        }

        if (isset($this->description)) {
            echo "  <description>\n".rtrim($this->description)."\n  </description>\n\n";
        }

        uasort($this->authors, array("CodeGen_PECL_Maintainer", "comp"));
        foreach ($this->authors as $maintainer) {
            echo $maintainer->packageXml2();
        }
        echo "\n";

        echo $this->release->packageXml2($this->license);

        echo "  <contents>\n";
        echo $this->packageXmlFileList();
        echo "  </contents>\n\n";

        echo "  <dependencies>\n";
        echo "    <required>\n";
        echo "      <php>\n";
        echo "        <min>".$this->minPhpVersion()."</min>\n";
        echo "      </php>\n";
        echo "      <pearinstaller>\n";
        echo "        <min>1.4.0a1</min>\n";
        echo "      </pearinstaller>\n";
        foreach ($this->otherExtensions as $ext) {
            echo $ext->packageXML2(array("REQUIRED", "CONFLICTS"));
        }
        echo $this->platform->packageXML2();
        echo "    </required>\n";

        $optional = "";
        foreach ($this->otherExtensions as $ext) {
            $optional.= $ext->packageXML2(array("OPTIONAL"));
        }
        if (!empty($optional)) {
          echo "    <optional>\n";
          echo $optional;
          echo "    </optional>\n";
        }

        echo "  </dependencies>\n\n";

        echo "  <providesextension>{$this->name}</providesextension>\n\n";

        if (count($this->with)) {
            echo "  <extsrcrelease>\n";
            foreach ($this->with as $with) {
                $configOption = "with-".$with->getName();
                echo "   <configureoption name=\"{$configOption}\" default=\"autodetect\" prompt=\"".$with->getName()." installation directory?\" />\n";
            }
            echo "  </extsrcrelease>\n\n";
        } else {
            echo "  <extsrcrelease/>\n\n";
        }

        echo "</package>\n";

        return $outfile->write();
    }

    // }}}

    /**
     * add a custom test case
     *
     * @access public
     * @param  object  a Test object
     */
    function addTest(CodeGen_PECL_Element_Test $test)
    {
        $name = $test->getName();

        if (isset($this->testcases[$name])) {
            return PEAR::raiseError("testcase '{$name}' added twice");
        }

        $this->testcases[$name] = $test;
        return true;
    }

    /**
     * Write test case files
     *
     * @access private
     */
    function writeTestFiles()
    {
        $testCount=0;
        @mkdir($this->dirpath."/tests");

        // function related tests
        foreach ($this->functions as $function) {
            $function->writeTest($this);
        }

        // class method related tests
        foreach ($this->classes as $class) {
            $class->writeTests($this);
        }

        // custom test cases (may overwrite custom function test cases)
        foreach ($this->testcases as $test) {
            $test->writeTest($this);
        }

        if (0 == count(glob($this->dirpath."/tests/*.phpt"))) {
            rmdir($this->dirpath."/tests");
        }
    }

    /**
    * Generate README file (custom or default)
    *
    * @access private
    * @param  string  directory to write to
    */
    function writeReadme()
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/README");

        $configOption = "";

        if (count($this->with)) {
            foreach ($this->with as $with) {
                $configOption.= "[--with-".$with->getName()."=...] ";
            }
        } else {
          $configOption.= "[--enable--".$this->name."] ";
        }

?>
This is a standalone PHP extension created using CodeGen_PECL <?php echo self::version(); ?>

HACKING
=======

There are two ways to modify an extension created using CodeGen_PECL:

1) you can modify the generated code as with any other PHP extension

2) you can add custom code to the CodeGen_PECL XML source and re-run pecl-gen

The 2nd approach may look a bit complicated but you have be aware that any
manual changes to the generated code will be lost if you ever change the
XML specs and re-run PECL-Gen. All changes done before have to be applied
to the newly generated code again.
Adding code snippets to the XML source itself on the other hand may be a
bit more complicated but this way your custom code will always be in the
generated code no matter how often you rerun CodeGen_PECL.

<?php if ($this->platform->test("unix")): ?>

BUILDING ON UNIX etc.
=====================

To compile your new extension, you will have to execute the following steps:

1.  $ ./phpize
2.  $ ./configure <?php echo $configOption."\n"; ?>
3.  $ make
4.  $ make test
5.  $ [sudo] make install

<?php endif; ?>

<?php if ($this->platform->test("windows")): ?>

BUILDING ON WINDOWS
===================

The extension provides the VisualStudio V6 project file

  <?php echo $this->name.".dsp" ?>

To compile the extension you open this file using VisualStudio,
select the apropriate configuration for your installation
(either "Release_TS" or "Debug_TS") and create "php_<?php echo $this->name; ?>.dll"

After successfull compilation you have to copy the newly
created "<?php echo $this->name; ?>.dll" to the PHP
extension directory (default: C:\PHP\extensions).

<?php endif; ?>

TESTING
=======

You can now load the extension using a php.ini directive

  extension="<?php echo $this->name; ?>.[so|dll]"

or load it at runtime using the dl() function

  dl("<?php echo $this->name; ?>.[so|dll]");

The extension should now be available, you can test this
using the extension_loaded() function:

  if (extension_loaded("<?php echo $this->name; ?>"))
    echo "<?php echo $this->name; ?> loaded :)";
  else
    echo "something is wrong :(";

The extension will also add its own block to the output
of phpinfo();

<?php

        $file->write();
    }

    /**
     * Return minimal PHP version required to support the requested features
     *
     * @return  string  version string
     */
    function minPhpVersion()
    {
        // min. default: 4.0
        $version = "4.0.0"; // TODO test for real lower bound

        // we only support the 5.0 (ZE2) OO api
        if (!empty($this->classes) || !empty($this->interfaces)) {
            $version = $this->maxVersion($version, "5.0.0");
        }

        // extension interdependencies only exist in 5.1 and above
        if (!empty($this->otherExtensions)) {
            $version = $this->maxVersion($version, "5.1.0rc1");
        }

        // check function requirements
        foreach ($this->functions as $function) {
          $version = $this->maxVersion($version, $function->minPhpVersion());
        }

        // check class requirements
        foreach ($this->classes as $class) {
          $version = $this->maxVersion($version, $class->minPhpVersion());
        }

        // check interface requirements
        foreach ($this->interfaces as $interface) {
          $version = $this->maxVersion($version, $interface->minPhpVersion());
        }

        return $version;
    }

    function maxVersion($v1, $v2)
    {
      return version_compare($v1, $v2) > 0 ? $v1 : $v2;
    }

    /**
     * Return minimal PHP version required to support the requested features
     *
     * @return  string  version string
     */
    function minPhpVersionId()
    {
       $id = explode('.', $this->minPhpVersion());

       return (int)$id[0] * 10000 + (int)$id[1] * 100 + (int)$id[2];
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
sgml-default-dtd-file:"'.str_repeat("../", $level).'manual.ced"
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

    /**
     * Show error message and bailout
     *
     * @param string  error message
     */
    function terminate($msg)
    {
        while (@ob_end_clean()); // purge output buffers

        $stderr = fopen("php://stderr", "w");
        if ($stderr) {
            fprintf($stderr, "%s\n", $msg);
            fclose($stderr);
        } else {
            echo "$msg\n";
        }
        exit(3);
    }

    /**
     * Return array of defined functions
     *
     * @return array
     */
    function getFunctions()
    {
        return $this->functions;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */

