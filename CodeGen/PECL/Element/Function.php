<?php
/**
 * Class describing a function within a PECL extension 
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
 * @version    CVS: $Id: Function.php,v 1.46 2007/04/25 13:59:09 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/** 
 * includes
 */
require_once "CodeGen/PECL/Element.php";

require_once "CodeGen/Tools/Tokenizer.php";

require_once "CodeGen/PECL/Tools/ProtoLexer.php";
require_once "CodeGen/PECL/Tools/ProtoParser.php";

/**
 * Class describing a function within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Function 
    extends CodeGen_PECL_Element 
{
    /**
     * The function name
     *
     * @var     string
     */
    protected $name = "";

    /**
     * name setter
     *
     * @param string
     */
    function setName($name) 
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid function name");
        }
            
        switch ($this->role) {
        case "internal":
            if (!$this->isInternalName($name)) {
                return PEAR::raiseError("'$name' is not a valid internal function name");
            }
            break;

        case "public":
            // keywords are not allowed as function names
            if (self::isKeyword($name)) {
                return PEAR::raiseError("'$name' is a reserved word which is not valid for function names");
            }
            // you should not redefine standard PHP functions
            foreach (get_extension_funcs("standard") as $stdfunc) {
                if (!strcasecmp($name, $stdfunc)) {
                    return PEAR::raiseError("'$name' is already the name of a PHP standard function");
                }
            }
            break;
        }

        $this->name = $name;
            
        return true;
    }

    /**
     * name getter
     *
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * distinguishable name getter
     *
     * here it's just the same as the plain name
     * but e.g. for class methods that wouldn't
     * be enough
     *
     * @return string
     */
    function getFullName()
    {
        return $this->name;
    }

    /**
     * A short description
     *
     * @var     string
     */
    protected $summary = "";

    /**
     * summary getter
     *
     * @param string
     */
    function setSummary($text)
    {
        $this->summary = $text;
        return true;
    }




    /**
     * A long description
     *
     * @var     string
     */
    protected $description  = "";

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
     * Type of function: internal, public
     *
     * @var     string
     */
    protected $role  = "public";

    /**
     * role setter
     * 
     * @param string
     */
    function setRole($role)
    {
        switch($role) {
        case "internal":
            if (!$this->isInternalName($this->name)) {
                return PEAR::raiseError("'{$this->name}' is not a valid internal function name");
            }
            break;

        case "public":
            break;

        case "private":
            return PEAR::raiseError("'private' functions are no longer supported, use global <code> sections instead");
            break;

        default:
            return PEAR::raiseError("'$role' is not a valid function role"); 
        }
            
        $this->role = $role;
            
        return true;
    }

    function getRole() 
    {
        return $this->role;
    }


    /**
     * Function has variable arguments "..."
     * 
     * @var    bool
     */
    protected $varargs = false;

    function setVarargs($varargs) 
    {
        $this->varargs = (bool)$varargs;
    }

    function getVarargs() 
    {
        return $this->varargs;
    }

    protected $varargsType = "mixed";

    function setVarargsType($type)
    {
        $type = strtolower($type);
        
        switch ($type) {
        case "bool":
        case "int":
        case "float":
        case "string":
        case "mixed":
            $this->varargsType = $type;
            return true;
        }
        
        return PEAR::raiseError("invalid vararg type '$type', only 'bool', 'int', 'float', 'string' \nand 'mixed' are supported for now");
    }

    /**
     * Function prototype
     *
     * @var     string
     */
    protected $proto = "void unknown(void)";

    /**
     * Function returntype (parsed from proto)
     *
     * @var     string
     */
    protected $returns = array();

    /**
     * Function parameters (parsed from proto)
     *
     * @var     array
     */
    protected $params = array();

    /**
     * Does this function have by-reference parameters?
     *
     * @var bool
     */
    protected $hasRefArgs = false;

    /**
     * Set parameter and return value information from PHP style prototype
     *
     * @param  string  PHP style prototype 
     * @param  object  Extension object owning this function
     * @return bool    Success status
     */
    function setProto($proto, $extension) 
    {
        $this->proto = $proto;

        if ($extension->haveVersion("1.1.0")) {
            $stat = $this->newSetProto2($proto, $extension);
        } else if ($extension->haveVersion("0.9.0rc1")) {
            $stat = $this->newSetProto($proto, $extension);
        } else {
            $stat = $this->oldSetProto($proto);
        }

        return $stat;
    }

    /**
     * Set parameter and return value information from PHP style prototype
     *
     * new (and hopefully final) version using a PHP_LexerGenerator and
     * PHP_ParserGenerator generated prototype parser
     *
     * @param  string  PHP style prototype 
     * @param  object  Extension object owning this function
     * @return bool    Success status
     */
    protected function newSetProto2($proto, $extension) 
    {
        try {
            $lex    = new CodeGen_PECL_Tools_ProtoLexer($proto);
            $parser = new CodeGen_PECL_Tools_ProtoParser($extension, $this);
            while ($lex->yylex()) {
                $parser->doParse($lex->token, $lex->value);
            }
            $parser->doParse(0, 0);
        } catch (Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    /**
     * Set parameter and return value information from PHP style prototype
     *
     * @param  string  PHP style prototype 
     * @param  object  Extension object owning this function
     * @return bool    Success status
     */
    protected function newSetProto($proto, $extension) 
    {
        $tokenGroups    = array();
        $optionals      = 0;
        $firstOptional  = 0;
        $squareBrackets = 0;
            
        $scanner = new CodeGen_Tools_Tokenizer($proto);

        // group #0 -> return type and function name
        $group = array();       
        while ($scanner->nextToken()) {
            if ($scanner->type === 'char' && $scanner->token === '(') {
                break;
            }
            $group[] = array($scanner->type, $scanner->token);
        }
        $tokenGroups[] = $group;
            
        // all following groups are parameters delimited by ','
        $group = array();       
        while ($scanner->nextToken()) {
            if ($scanner->type === 'char') {
                if ($scanner->token === '[') {
                    $squareBrackets++;
                    if (!$firstOptional) {
                        if (count($group)) {
                            $tokenGroups[] = $group;
                            $group         = array();
                        }
                        $firstOptional = count($tokenGroups);
                    }
                    continue;
                } 
                    
                if ($scanner->token === ',') {
                    if (count($group)) {
                        $tokenGroups[] = $group;
                        $group         = array();
                    }
                    continue;
                } 
                    

                if ($scanner->token === ']') {
                    $squareBrackets--;
                    continue;
                }

                if ($scanner->token === ')') {
                    $tokenGroups[] = $group;
                    $group         = array();
                    break;
                } 
            }

            $group[] = array($scanner->type, $scanner->token);
        }

        if ($squareBrackets > 0) {
            return PEAR::raiseError("missing closing ']'");
        }
        if ($squareBrackets < 0) {
            return PEAR::raiseError("to many closing ']'");
        }

            
        //
        // get return type and function name
        //
        $tokens = array_shift($tokenGroups);
            
        // first the function name
        list($type, $token) = array_pop($tokens);
        if ($type !== 'char' || $token !== '@') { // @ is allowed as function name placeholder
            if ($type !== 'name' || !self::isName($token)) {
                return PEAR::raiseError("'$token' is not a valid function name");
            }
            if (self::isKeyword($token)) {
                return PEAR::raiseError("keyword '$token' can't be used as function name");
            }
        }

        $functionName = $token;
        // function name is not really used, taken from <function> tag instead
        // TODO: add WARNING for mismatch?
        if (! empty($this->name)) {
            $functionName = $this->name;
        } else {
            $this->name = $functionName;
        }
            
        // then the return type
        list($type, $token) = array_shift($tokens);
        if ($type !== 'name' || !self::isType($token)) {
            return PEAR::raiseError("'$token' is not a valid return type");
        }
        $returnType = $token;
            
        // some types may carry a name
        if ($token === "object" || $token == "resource") {
            if (isset($tokens[0][0]) && $tokens[0][0] === 'name') {
                list($type, $token) = array_shift($tokens);
                $returnSubtype = $token;
            }
        }
            
        // return by reference?
        if (count($tokens) && $tokens[0][0] === 'char' && ($tokens[0][1] === '&' || $tokens[0][1] === '@')) {
            list($type, $token) = array_shift($tokens);
            $returnByRef = true;
        }
            
        // any tokens left?
        if (count($tokens)) {
            return PEAR::raiseError("extra token '".$tokens[0][1]."' in function prototype");
        }
            
        // 
        // now the parameters one by one
        // 
        $params = array();
        $vararg = false;
        while ($tokens = array_shift($tokenGroups)) {
            if ($vararg) {
                return PEAR::raiseError("no further parameters are allowed after '...'");
            }
                
            $param = array();
                
            // check for default value assignment
            $equals  = false;
            $default = array();
            foreach ($tokens as $key => $value) {
                if ($value[0] === 'char' && $value[1] === '=') {
                    $equals = $key; 
                    break;
                }
            }
            if ($equals !== false) {
                while (count($tokens) > $equals + 1) {
                    array_unshift($default, array_pop($tokens));
                }
                array_pop($tokens);
            }
                
            // get parameter name
            list($type, $token) = array_pop($tokens);
            if ($type !== 'name') {
                return PEAR::raiseError("'$token' is not a valid parameter name (1)");
            }
            $param['name'] = $token;
                
            // then the parameter type
            list($type, $token) = array_shift($tokens);
            if ($type !== 'name' || !self::isType($token)) {
                switch ($param["name"]) {
                case "void":
                    if (!empty($params)) {
                        return PEAR::raiseError("only the first (and only) paramter can be of type void");
                    }
                    $param["type"] = $param["name"];
                    $param["name"] = "";
                    break;
                case "...":
                    if ($optionals) { // TODO not set yet ???
                        return PEAR::raiseError("'...' varargs can't be combined with optional args");
                    }
                    $vararg        = true;
                    $param["type"] = $param["name"];
                    $param["name"] = "";
                    break;
                default:
                    return PEAR::raiseError("'$token' is not a valid type for parameter '".$param['name']."'");
                }
            } else {
                $param['type'] = $token;
            }
                
            if (!empty($param["name"]) && !self::isName($param["name"])) {
                return PEAR::raiseError("'{$param[name]}' is not a valid parameter name (2)");
            }

            // some types may carry a name
            if ($token === "object" || $token === "resource") {
                if ($tokens[0][0] === 'name') {
                    list($type, $token) = array_shift($tokens);
                    $param['subtype'] = $token;
                }
            }
                
            //  pass by reference?
            if (count($tokens) && ($tokens[0][0] === 'char') && ($tokens[0][1] === '&' || $tokens[0][1] === '@')) {
                list($type, $token) = array_shift($tokens);
                if ($param['type'] != "array" && $param['type'] != "mixed" && $param['type'] != 'object') {
                    return PEAR::raiseError("only 'array', 'object' and 'mixed' arguments may be passed by reference, '$param[name]' is of type '$param[type]'");
                }
                $param['byRef'] = true;
                $this->hasRefArgs = true;
            }
                
            // any tokens left?
            if (count($tokens)) {
                return PEAR::raiseError("extra token '".$tokens[0][1]."' in specification of parameter '$param[name]'");
            }
                
            // do we have a default value?
            switch (count($default)) {
            case 0:
                break;
            case 1:
                list($type, $token) = array_shift($default);
                switch ($type) {
                case 'string': 
                case 'char':
                    $param['default'] = '"'.str_replace('"', '\"', $token).'"';
                    break;
                case 'numeric': 
                    $param['default'] = $token;
                    break;
                case 'name':
                    switch (strtolower($token)) {
                        // first check for 'known' PHP constants
                    case 'true':
                    case 'false':
                    case 'null':
                    case 'array()':
                        $param['default'] = $token; 
                        break;                          
                    default:
                        // now see if this is a constand defined by this extension
                        $constant = $extension->getConstant($token);
                        if ($constant) {
                            $param["default"] = $constant->getValue();
                        } else {
                            return PEAR::raiseError("invalid default value '$token' specification for parameter '$param[name]' ($type)");
                        }
                    }
                    break;
                default:
                    return PEAR::raiseError("invalid default value '$token' specification for parameter '$param[name]' ($type)");
                }
                break;
            default:
                return PEAR::raiseError("invalid default value '$token' specification for parameter '$param[name]' ($type)");
            }
                
            if ($firstOptional && count($params)+1 >= $firstOptional) {
                $param['optional'] = true;
                $optionals++;
            }
                
            $params[] = $param;
        }

        $this->returns['type']  = $returnType;

        if (isset($returnSubtype)) {
            $this->returns['subtype'] = $returnSubtype;
        }

        if (isset($returnByRef)) {
            $this->returns["byRef"] = true;
        }

        $this->params   = $params;
        $this->vararg   = $vararg;

        return true;
    }


    function oldSetProto($proto) 
    {
        // This is the classic setProto() version, we keep it for now
        // as the new version is not 100% backwards compatible yet
            
        // 'tokenize' it
        $tokens = array();

        // we collect valid C names as Strings, any other character for itself, blanks are skipped
        // TODO: this does no longer work if we ever add default values ...
        $len = strlen($proto);
        $name = "";
        for ($n = 0; $n < $len; $n++) {
            $char = $proto{$n};
            if (ctype_alpha($char) || $char == '_' || ($n && ctype_digit($char))) {
                $name .= $char;
            } else if ($char == '.' && $proto{$n+1} == '.' && $proto{$n+2} == '.') {
                $name = "...";
                $n    += 2;
            } else {
                if ($name) $tokens[] = $name;
                $name = "";
                if (trim($char)) {
                    $tokens[] = $char;
                }
            }
        }

        if ($name) {
            $tokens[] = $name;
        }

        $n       = 0;
        $opts    = 0;
        $numopts = 0;
        $params  = array();

        $returnType = ($this->isType($tokens[$n])) ? $tokens[$n++] : "void";

        $functionName = $tokens[$n++];

        if ($returnType === "resource" && $tokens[$n] !== "(") {
            $returnSubtype = $functionName;
            $functionName  = $tokens[$n++];
        }

        // function name is not really used, taken from <function> tag instead
        // TODO: add WARNING for mismatch?
        if (! empty($this->name)) {
            $functionName = $this->name;
        } else {
            $this->name = $functionName;
        }

        if ($tokens[$n++] != '(') return PEAR::raiseError("'(' expected instead of '$tokens[$n]'");
            
        if ($tokens[$n] == ')') {
            /* done */
        } else if ($tokens[$n] == '...') {
            $params[0]['type'] = "...";
            $n++;
        } else {
            for ($param = 0; $tokens[$n]; $n++, $param++) {
                if ($tokens[$n] == '[') {
                    $params[$param]['optional'] = true;
                    $opts++;
                    $n++;
                    if ($param > 0) {
                        if ($tokens[$n] != ',') {
                            return PEAR::raiseError("',' expected after '[' instead of '$token[$n]'");
                        }
                        $n++;
                    }
                }

                if (!$this->isType($tokens[$n])) {
                    return PEAR::raiseError("type name expected instead of '$tokens[$n]'");
                }

                $params[$param]['type'] = $tokens[$n];
                $n++;

                if ($tokens[$n] == "&" || $token[$n] == "@") {
                    $params[$param]['byRef'] = true;
                    $n++;
                }

                if ($this->isName($tokens[$n])) {
                    $params[$param]['name']=$tokens[$n];
                    $n++;
                }

                if ($tokens[$n] == "&" || $token[$n] == "@") {
                    $params[$param]['byRef'] = true;
                    $n++;
                }

                if ($params[$param]['type'] === "resource" && $this->isName($tokens[$n])) {
                    $params[$param]['subtype'] = $params[$param]['name'];
                    $params[$param]['name'] = $tokens[$n];
                    $n++;
                }

                if ($tokens[$n] == '[') {
                    $n--;
                    continue;
                }

                if ($tokens[$n] == ',') continue;
                if ($tokens[$n] == ']') break;
                if ($tokens[$n] == ')') break;            

            }

            $numopts = $opts;
            while ($tokens[$n] == ']') {
                $n++;
                $opts--;
            }
            if ($opts != 0) {
                return PEAR::raiseError("'[' / ']' count mismatch");
            }
        } 

        if ($tokens[$n] != ')') {
            return PEAR::raiseError("')' expected instead of '$tokens[$n]'");
        }

        $this->returns['type']  = $returnType;

        if (isset($returnSubtype)) {
            $this->returns['subtype'] = $returnSubtype;
        }

        $this->params   = $params;

        return true;
    }


    /**
     * Code snippet
     *
     * @var    string
     */
    protected $code = "";

    /**
     * Source file of code snippet
     *
     * @var    string
     */
    protected $codeFile = "";

    /**
     * Source line of code snippet
     *
     * @var    int
     */
    protected $codeLine = 0;

    /**
     * Code setter
     *
     * @param string code snippet
     * @param int    source line
     * @param int    source filename
     */
    function setCode($code, $line = 0, $file = "")
    {
        $this->code     = $code;
        $this->codeFile = $file;
        $this->codeLine = $line;
        return true;
    }

    /**
     * Code getter
     *
     * @return string
     */
    function getCode()
    {
        return $this->code;
    }





    /**
     * test code snippet
     *
     * @var string
     */
    protected $testCode = "echo 'OK'; // no test case for this function yet";

    /**
     * testCode setter
     *
     * @param  string code snippet
     */
    function setTestCode($code)
    {
        $this->testCode = $code;
    }

    /**
     * testCode getter
     *
     * @return string
     */
    function getTestCode()
    {
        return $this->testCode;
    }


    /**
     * expected test result string
     *
     * @var array
     */
    protected $testResult = array();
 
    /**
     * testResult setter
     *
     * @param  string result text
     * @param  string test output comparison mode
     */
    function setTestResult($text, $mode = "plain")
    {
        $this->testResult = array("result" => $text, "mode" => $mode);
    }

    /**
     * testResult getter
     *
     * @return array
     */
    function getTestResult()
    {
        return $this->testResult;
    }


    /**
     * test code description
     *
     * @var string
     */
    protected $testDescription = "";

    /**
     * testDescritpion setter
     *
     * @param  string text
     */
    function setTestDescription($text)
    {
        $this->testDescription = $text;
    }

    /**
     * testDescription getter
     *
     * @return string
     */
    function getTestDescription()
    {
        return $this->testDescription;
    }


    /**
     * test additional skipif condition
     *
     * @var string
     */
    protected $testSkipIf = "";

    /**
     * testSkipIf setter
     *
     * @param  string code snippet
     */
    function setTestSkipIf($code)
    {
        $this->testSkipIf = $code;
    }

    /**
     * testSkipIf getter
     *
     * @return string
     */
    function getTestSkipIf()
    {
        return $this->testSkipIf;
    }



    /**
     * test additional ini condition
     *
     * @var string
     */
    protected $testIni = "";

    /**
     * testIni setter
     *
     * @param  string code snippet
     */
    function setTestIni($code)
    {
        $this->testIni = $code;
    }

    /**
     * testIni getter
     *
     * @return string
     */
    function getTestIni()
    {
        return $this->testIni;
    }









    /**
     * Check whether a function name is already used internally
     *
     * @param  string  function name
     * @return bool    true if function name is already used internally
     */
    function isInternalName($name)
    {
        switch ($name) {
        case "MINIT":
        case "MSHUTDOWN":
        case "RINIT":
        case "RSHUTDOWN":
        case "MINFO":
            return true;
        }

        return false;
    }


    /** 
     * Helper for cCode
     *
     * @param  string  Parameter spec. array
     * @param  string  default value for type
     * @return string  default value
     */
    function defaultval($param, $default) 
    {
        if (isset($param["default"])) {
            if (is_object($param["default"])) {
                return $param["default"]->getValue();
            } 
            return $param["default"];
        }

        return $default;
    }


    /**
     * Hook for parameter parsing API function 
     *
     * @param  string  Argument string
     * @param  array   Argument variable pointers
     * @param  int     Return value for number of arguments
     */
    protected function parseParameterHook($argString, $argPointers, &$count)
    {
        $count = count($this->params);

        if ($this->varargs) {
            $argc = sprintf("MIN(ZEND_NUM_ARGS(), %d)", $count);
        } else if ($count > 0) {
            $argc = "ZEND_NUM_ARGS()";
        } 
       
        if (isset($argc)) {
            $parse_call = "zend_parse_parameters($argc TSRMLS_CC, \"$argString\", ".join(", ", $argPointers).")";
        } else {
            $parse_call = "zend_parse_parameters_none()";
        }
        
        return "
    if ($parse_call == FAILURE) {
        return;
    }
";
    }


    /**
     * Generate local variable declarations
     *
     * @return string C code snippet
     */
    function localVariables($extension) 
    {
        $code = "";

        // for functions returning a named resource we create payload pointer variable
        if ($this->returns['type'] === "resource") {
            if (isset($this->returns['subtype'])) {
                $resource = $extension->getResource($this->returns['subtype']);
                if ($resource) {
                    $payload  = $resource->getPayload();
                    if ($resource->getAlloc()) {
                        $code .= "    $payload * return_res = ($payload *)ecalloc(1, sizeof($payload));\n";
                    } else {
                        $code .= "    $payload * return_res;\n";
                    }
                } else {
                    $code .= "    void * return_res;\n";
                }
            } else {
                $code .= "    void * return_res;\n";
            }
            $code .= "    long return_res_id = -1;\n";
        }

        return $code;
    }

    /**
     * Create C code implementing the PHP userlevel function
     *
     * @param  class Extension  extension the function is part of
     * @return string           C code implementing the function
     */
    function cCode($extension) 
    {
        $code = "\n";

        switch ($this->role) {
        case "public":
            $code .= $this->ifConditionStart($extension);

            // function prototype comment
            $code .= "/* {{{ proto {$this->proto}\n  ";
            if (!empty($this->summary)) {
                $code .= $this->summary;
            }
            $code .= " */\n";

            // function declaration
            $code .= $this->cProto()."\n";
            $code .= "{\n";
                
            $code .= $this->localVariables($extension);

            $var_decl = "\n";
            $var_code = "\n";

            // now we create local variables for all parameters
            // at the same time we put together the parameter parsing string
            if (is_array($this->params) && count($this->params)) {

                $argString   = "";
                $argPointers = array();
                $optional    = false;
                $postProcess = "";
                $zvalType    = false;

                foreach ($this->params as $param) {
                    if ($param["type"] === "void"  || $param["type"] === "...") {
                        continue;
                    }

                    $name = $param['name']; 
                        
                    if ($param['type'] == "resource" && $extension->haveVersion("1.0.0alpha")) {
                        $argPointers[] = "&{$name}_res";
                    } else {
                        $argPointers[] = "&$name";
                    }

                    if (isset($param['optional']) && !$optional) {
                        $optional   = true;
                        $argString .= "|";
                    }

                    switch ($param['type']) {
                    case "bool":
                        $argString .= "b";
                        $default    = $this->defaultval($param, "0");
                        $var_decl  .= "    zend_bool $name = $default;\n";
                        break;

                    case "int":
                        $argString .= "l";
                        $default    = $this->defaultval($param, "0");
                        $var_decl  .= "    long $name = $default;\n";
                        break;

                    case "float":
                        $argString .= "d";
                        $default    = $this->defaultval($param, "0.0");
                        $var_decl  .= "    double $name = $default;\n";
                        break;

                    case "string":
                        $argString    .= "s";
                        $default       = $this->defaultval($param, "NULL");
                        $var_decl     .= "    const char * $name = $default;\n";
                        $var_decl     .= sprintf("    int {$name}_len = %d;\n", 
                                                 $default==="NULL" ? 0 : strlen($default) - 2);
                        $argPointers[] = "&{$name}_len";
                        break;

                    case "unicode":
                        $argString    .= "u";
                        $default       = $this->defaultval($param, "NULL");
                        $var_decl     .= "    const char * $name = $default;\n";
                        $var_decl     .= sprintf("    int {$name}_len = %d;\n", 
                                                 $default==="NULL" ? 0 : strlen($default) - 2);
                        $argPointers[] = "&{$name}_len";
                        break;

                    case "text":
                        $argString    .= "t";
                        $default       = $this->defaultval($param, "NULL");
                        $var_decl     .= "    const char * $name = $default;\n";
                        $var_decl     .= sprintf("    int {$name}_len  = %d;\n", 
                                                 $default==="NULL" ? 0 : strlen($default) - 2);
                        $var_decl     .= "    int {$name}_type = IS_STRING;\n"; // TODO depends on input encoding
                        $argPointers[] = "&{$name}_len";
                        break;

                    case "array":
                        $zvalType     = true;
                        $argString   .= "a";
                        $var_decl    .= "    zval * $name;\n";
                        $var_code    .= "    MAKE_STD_ZVAL($name);\n    array_init($name);\n";
                        $var_decl    .= "    HashTable * {$name}_hash = NULL;\n";
                        $postProcess .= "    {$name}_hash = HASH_OF($name);\n";
                        break;

                    case "object": 
                        $zvalType = true;
                        $var_decl.= "    zval * $name = NULL;\n";
                        if (isset($param['subtype'])) {
                            $argString    .= "O";
                            $argPointers[] = "$param[subtype]_ce_ptr";
                        } else {
                            $argString    .= "o";
                        }
                        break;

                    case "resource":
                        $zvalType   = true;
                        $resource   = false;
                        $argString .= "r";

                        if ($extension->haveVersion("1.0.0alpha")) {
                            $resVar     = $name."_res";
                            $payloadVar = $name;
                            $idVar      = $name."_resid";
                        } else {
                            $resVar     = $name;
                            $payloadVar = "res_".$name;
                            $idVar      = $name."_id";
                        }

                        $code .= "    zval * $resVar = NULL;\n";
                        $code .= "    int $idVar = -1;\n";

                        if (isset($param['subtype'])) {
                            $resource = $extension->getResource($param['subtype']);                            
                        }

                        if ($resource) {
                            if ($extension->haveVersion("1.0.0dev")) {
                                $varname = $name;
                            } else {
                                $varname = "res_{$name}";
                            }
                            $code .= "    ".$resource->getPayload()." * $payloadVar;\n";
                                
                            $postProcess .= "    ZEND_FETCH_RESOURCE($payloadVar, ".$resource->getPayload()." *, &$resVar, $idVar, \"$param[subtype]\", le_$param[subtype]);\n";
                        } else {
                            $postProcess .="    ZEND_FETCH_RESOURCE(???, ???, $resVar, $idVar, \"???\", ???_rsrc_id);\n";
                        }
                        break;

                    case "stream":
                        $zvalType     = true;
                        $argString   .= "r";
                        $var_decl    .= "    zval * {$name}_zval = NULL;\n";
                        $var_decl    .= "    php_stream * $name = NULL;\n";
                        $postProcess .= "    php_stream_from_zval($name, &{$name}_zval);\n";
                        break;

                    case "callback": 
                        $postProcess .= "    if (!zend_is_callable({$name}, 0, NULL)) {\n";
                        $postProcess .= "      php_error(E_WARNING, \"Invalid comparison function.\");\n";
                        $postProcess .= "      return;";
                        $postProcess .= "    }\n";
                        /* fallthru */
                    case "mixed":
                        $zvalType = true;
                        $argString .= "z";
                        $var_decl  .= "    zval * {$name} = NULL;\n";
                        break;                          
                    }

                    if (empty($param['byRef']) && ($param['type'] == 'mixed' || $param['type'] == 'array')) {
                        $argString .= "/";
                    } else if ($param['type'] == 'object') {
                        // nothing to do as objects are passed by reference anyway
                    } else if (!$zvalType) {
                        // TODO: pass by ref for 'simple' types requires further thinking
                    }
                }
            } 

            if ($this->varargs) {
                $var_decl .= "\n";
                $var_decl .= "    int varargc;\n";
                $var_decl .= "    zval ***real_argv;\n";
                switch ($this->varargsType) {
                case "bool":
                    $var_decl .= "    zend_bool *varargv;\n";
                    break;
                case "int":
                    $var_decl .= "    long *varargv;\n";
                    break;
                case "float":
                    $var_decl .= "    double *varargv;\n";
                    break;
                case "string":
                    $var_decl .= "    char **varargv;\n";
                    $var_decl .= "    int   *varargv_len;\n";
                    break;
                case "mixed":
                default:
                    $var_decl .= "    zval ***varargv;\n";
                    break;
                }
                $var_decl .= "\n";
            }

            $varargs_offset = 0;
            // now we do the actual parameter parsing

            if (empty($argString)) {
                if ((!empty($this->params) && $this->params[0]['type'] == "...") // old parser?
                    || $this->varargs) {
                } else {
                    $var_code .= "    if (ZEND_NUM_ARGS()>0)  {\n        WRONG_PARAM_COUNT;\n    }\n\n";
                }
            } else {
                $var_code .= $this->parseParameterHook($argString, $argPointers, $varargs_offset);
                    
                if (!empty($postProcess)) {
                    $var_code.= "$postProcess\n\n";
                }
            }

                    
            $code .= "$var_decl\n";
            $code .= "$var_code\n";

            if ($this->varargs) {
                $code .= "\n    varargc = ZEND_NUM_ARGS();\n";
                $code .= "    real_argv = (zval ***)calloc(sizeof(zval **), varargc);\n";
                $code .= "    zend_get_parameters_array_ex(varargc, real_argv);\n";
                $code .= "    varargc -= $varargs_offset;\n";

                switch ($this->varargsType) {
                case "bool":
                    $code .= "    varargv = (zend_bool *)calloc(sizeof(zend_bool), varargc);\n";
                    $code .= "    {\n";
                    $code .= "      int i;\n";
                    $code .= "      for (i = 0; i < varargc; i++) {\n";
                    $code .= "        convert_to_boolean_ex(real_argv[i + $varargs_offset]);\n";
                    $code .= "        varargv[i] = Z_BVAL_PP(real_argv[i + $varargs_offset]);\n";
                    $code .= "      }\n";
                    $code .= "    }\n";
                    break;
                case "int":                    
                    $code .= "    varargv = (long *)calloc(sizeof(long), varargc);\n";
                    $code .= "    {\n";
                    $code .= "      int i;\n";
                    $code .= "      for (i = 0; i < varargc; i++) {\n";
                    $code .= "        convert_to_long_ex(real_argv[i + $varargs_offset]);\n";
                    $code .= "        varargv[i] = Z_LVAL_PP(real_argv[i + $varargs_offset]);\n";
                    $code .= "      }\n";
                    $code .= "    }\n";
                    break;
                case "float":
                    $code .= "    varargv = (double *)calloc(sizeof(double), varargc);\n";
                    $code .= "    {\n";
                    $code .= "      int i;\n";
                    $code .= "      for (i = 0; i < varargc; i++) {\n";
                    $code .= "        convert_to_double_ex(real_argv[i + $varargs_offset]);\n";
                    $code .= "        varargv[i] = Z_DVAL_PP(real_argv[i + $varargs_offset]);\n";
                    $code .= "      }\n";
                    $code .= "    }\n";
                    break;
                case "string":
                    $code .= "    varargv = (char **)calloc(sizeof(char *), varargc);\n";
                    $code .= "    varargv_len = (int *)calloc(sizeof(int), varargc);\n";
                    $code .= "    {\n";
                    $code .= "      int i;\n";
                    $code .= "      for (i = 0; i < varargc; i++) {\n";
                    $code .= "        convert_to_string_ex(real_argv[i + $varargs_offset]);\n";
                    $code .= "        varargv[i] = Z_STRVAL_PP(real_argv[i + $varargs_offset]);\n";
                    $code .= "        varargv_len[i] = Z_STRLEN_PP(real_argv[i + $varargs_offset]);\n";
                    $code .= "      }\n";
                    $code .= "    }\n";
                    break;
                case "mixed":
                default:
                    $code .= "    varargv = real_argv + $varargs_offset;\n";
                    break;
                }
            } 
            

            // for functions returning an array we initialize return_value
            if ($this->returns['type'] === "array") {
                $code.="    array_init(return_value);\n\n";
            }

            if ($this->code) {
                if ($extension->getLinespecs()) {
                    // generate #line preprocessor directive
                    if ($this->codeLine) {
                        $linedef = "#line {$this->codeLine}";
                        if ($this->codeFile) {
                            $linedef.= ' "'.$this->codeFile.'"';
                        }
                        $linedef.= "\n";
                    } else {
                        $linedef = "";
                    }
                }

            $code .= $extension->codegen->varblock($linedef . $this->code); 
                // free varargs array if exists
                if ($this->varargs) {
                    $code .= "\n    free(real_argv);\n";
                    switch ($this->varargsType) {
                    case "string":
                        $code .="    free(varargv_len);\n";
                    case "bool":
                    case "int":
                    case "float":
                        $code .="    free(varargv);\n";
                        break;
                    default:
                        break;
                    }
                }

                // when a function returns a named resource we know what to do
                if ($this->returns['type'] == "resource" && isset($this->returns['subtype'])) {
                    $code .= "\n    return_res_id = ZEND_REGISTER_RESOURCE(return_value, return_res, le_"
                        .$this->returns['subtype'].");\n";
                }
            } else {
                // no code snippet was given so we produce a suggestion for the return statement
                $code .= "    php_error(E_WARNING, \"{$this->name}: not yet implemented\"); RETURN_FALSE;\n\n";
                    
                switch ($this->returns['type']) {
                case "void":
                    break;
                        
                case "bool":
                    $code .= "    RETURN_FALSE;\n"; 
                    break;
                        
                case "int":
                    $code .= "    RETURN_LONG(0);\n"; 
                    break;
                        
                case "float":
                    $code .= "    RETURN_DOUBLE(0.0);\n";
                    break;
                        
                case "string":
                    $code .= "    RETURN_STRINGL(\"\", 0, 1);\n";
                    break;

                case "array":
                    $code .= "    array_init(return_value);\n";
                    break;
                        
                case "object": 
                    $code .= "    object_init(return_value)\n";
                    break;
                        
                case "resource":
                    if (isset($this->returns['subtype'])) {
                        $code .= "    ZEND_REGISTER_RESOURCE(return_value, return_res, le_"
                            .$this->returns['subtype'].");\n";
                    } else {
                        $code .= "    /* RETURN_RESOURCE(...); */\n";
                    }
                    break;
                        
                case "stream":
                    $code .= "    /* php_stream_to_zval(stream, return_value); */\n";
                    break;
                        
                case "mixed":
                    $code .= "    /* RETURN_...(...); */\n";              
                    break;
                        
                default: 
                    $code .= "    /* UNKNOWN RETURN TYPE '".$this->returns['type']."' */\n";
                    break;
                }
            }
                
            $code .= "}\n/* }}} {$this->name} */\n\n";

            $code .= $this->ifConditionEnd($extension);

            break;
                
        case "internal":
            if (!empty($this->code)) {
                $code .= $extension->codegen->varblock($this->code."\n");
            }
            break;
        }
        return $code;
    }
        


    /**
     * Create DocBook reference entry for the function
     *
     * @param  string  base (currently not used)
     * @return string  DocBook XML code
     */
    function docEntry($base) 
    {
        $xml = 
            '<?xml version="1.0" encoding="iso-8859-1"?>
<!-- '.'$'.'Revision: 1.0 $ -->
  <refentry id="function.' . strtolower(str_replace("_", "-", $this->name)) . '">
   <refnamediv>
    <refname>' . $this->name . '</refname>
    <refpurpose>' . $this->summary . '</refpurpose>
   </refnamediv>
   <refsect1>
    <title>Description</title>
     <methodsynopsis>
';

        $returns = $this->returns['type'];
        if (isset($this->returns['subtype'])) {
            $returns .= " ".$this->returns['subtype'];
        }
        if (@$this->returns['byref']) {
            $returns .= " &";
        }
            
        $xml .= "      <type>$returns</type><methodname>{$this->name}</methodname>\n";
        if (empty($this->params) || $this->params[0]["type"] === "void") {
            $xml .= "      <void/>\n";
        } else if ($this->params[0]["type"] === "...") {
            $xml .= "      <methodparam choice='opt' rep='repeat'><type>mixed</type><parameter>...</parameter></methodparam>\n";
        } else {
            foreach ($this->params as $key => $param) {
                if (isset($param['optional'])) {
                    $xml .= "      <methodparam choice='opt'>";
                } else {
                    $xml .= "      <methodparam>";
                }
                $xml .= "<type>$param[type]</type><parameter>";
                if (isset($param['byRef'])) {
                    $xml .= "&amp;"; 
                }
                $xml .= "$param[name]</parameter>";
                $xml .= "</methodparam>\n";
            }
        }


        $description = $this->description;

        if (!strstr($this->description, "<para>")) {
            $description = "     <para>\n$description     </para>\n";
        }

        $xml .= 
            '     </methodsynopsis>
'.$description.'
   </refsect1>
  </refentry>
';
 
        return $xml;
    }

        

    /**
     * write test case for this function
     *
     * @param  class Extension  extension the function is part of
     */
    function writeTest(CodeGen_PECL_Extension $extension) 
    {
        $test = $this->createTest($extension);

        if ($test instanceof CodeGen_PECL_Element_Test) {
            $test->writeTest($extension);
        }
    }

    /**
     * Create test case for this function
     *
     * @param  object  extension the function is part of
     * @return object  generated test case
     */
    function createTest(CodeGen_PECL_Extension $extension) 
    {
        if (!$this->testCode) {
            return false;
        }

        $test = new CodeGen_PECL_Element_Test;
            
        $test->setName($this->name);
        $test->setTitle($this->name."() function");
            
        if ($this->testDescription) {
            $test->setDescription($this->testDescription);
        }
            
        if ($this->testIni) {
            $test->addIni($this->testIni);
        }
            
        $test->setSkipIf("!extension_loaded('".$extension->getName()."')");
        if ($this->ifCondition) {
            $test->addSkipIf("!function_exists('{$this->name}')", "not compiled in ($this->ifCondition)");
        }
        if ($this->testSkipIf) {
            $test->addSkipIf($this->testSkipIf);
        }
            
        $test->setCode($this->testCode);
            
        if (!empty($this->testResult)) {
            $test->setOutput($this->testResult['result']);
            if (isset($this->testResult['mode'])) {
                $test->setMode($this->testResult['mode']);
            }
        }
            
        return $test;
    }


    /** 
     * C function signature
     *
     * @return  string  C snippet
     */
    function cProto()
    {
        return "PHP_FUNCTION({$this->name})";
    }

    /**
     * Create C header entry for userlevel function
     *
     * @param  class Extension  extension the function is part of
     * @return string           C header code snippet
     */
    function hCode($extension) 
    {
        $code = $this->ifConditionStart();

        $code .= $this->cProto();
        if ($code) {
            $code.= ";\n";
        }

        $code.= $this->argInfoCode($this->params);

        $code.= $this->ifConditionEnd();

        return $code;
    }


    /**
     * Code needed ahead of the function table 
     *
     * @param  array 
     * @return string
     */
    function argInfoCode($params) 
    {
        // TODO only generate code for versions actually requested
        // TODO only allow null on objects/arrays with default=NULL ?

        $argInfoName = $this->argInfoName();

        $code = "";

        // generate refargs mask if needed
        $code.= "#if (PHP_MAJOR_VERSION >= 5)\n";

        $minArgs = 0;
        foreach ($params as $param) {
            if (isset($param["optional"])) break;
            $minArgs++;
        }

        $code.= sprintf("ZEND_BEGIN_ARG_INFO_EX($argInfoName, ZEND_SEND_BY_VAL, ZEND_RETURN_%s, %d)\n",
                       isset($this->returns["byRef"]) ? "REFERENCE" : "VALUE",
                       $minArgs);

        foreach ($params as $param) {
            switch ($param["type"]) {
            case 'object':
                $code.= sprintf("  ZEND_ARG_OBJ_INFO(%d, %s, %s, 1)\n", 
                                isset($param["byRef"]), 
                                $param["name"],
                                $param["subtype"]);
                break;
            case 'array':
                $code.= "#if (PHP_MINOR_VERSION > 0)\n";
                $code.= sprintf("  ZEND_ARG_ARRAY_INFO(%d, %s, %d)\n", 
                                isset($param["byRef"]), 
                                $param["name"],
                                1 /*allow NULL*/);
                $code.= "#else\n";
                $code.= sprintf("  ZEND_ARG_INFO(%d, %s)\n", 
                                isset($param["byRef"]), 
                                $param["name"],
                                1 /*allow NULL*/);
                $code.= "#endif\n";
                break;
            default:
                $code.= sprintf("  ZEND_ARG_INFO(%d, %s)\n", 
                                isset($param["byRef"]), 
                                $param["name"]);
                break;
            }
        }

        $code.= "ZEND_END_ARG_INFO()\n";

        $code.= "#else /* PHP 4.x */\n";

        if ($this->hasRefArgs) {
            $code.= "static unsigned char {$argInfoName}[] = {".count($params);
            foreach ($params as $param) {
                $code.= ", ". (isset($param["byRef"]) ? "BYREF_FORCE" : "BYREF_NONE");
            }
            $code.="};\n";
        } else {
            $code .= "#define $argInfoName NULL\n";
        }
        $code.= "#endif\n\n";
        
        return $code;
    } 

    /**
     * Name for ARG_INFO definition
     *
     * @return string
     */
    function argInfoName()
    {
        return $this->name."_arg_info";
    }

    /**
     * Generate registration entry for extension function table
     *
     * @return string
     */
    function functionEntry()
    {
        $code = $this->ifConditionStart();

        $code .= sprintf("    PHP_FE(%-20s, %s)\n", $this->name, "{$this->name}_arg_info");

        $code.= $this->ifConditionEnd();

        return $code;
    }

    function addParam($param) 
    {
        foreach ($this->params as $p) {
            if ($param["name"] == $p["name"]) {
                return PEAR::raiseError("Parameter '".$param['name']."' already declared");
            }
        }

        $this->params[] = $param;
        if (@$param['byRef']) {
            $this->hasRefArgs = true;
        }

        return true;
    }

    function setReturns($returns)
    {
        $this->returns  = $returns;
    }

    function ifConditionStart($extension = false)
    {
        $code = parent::ifConditionStart();

        if ($extension) {
            $params = $this->params;
            $params[] = $this->returns;
            
            foreach ($params as $param) {
                if ($param["type"] == "resource" && isset($param['subtype'])) {
                    $obj = $extension->getResource($param['subtype']);
                } else if ($param["type"] == "object" && isset($param['subtype'])) {
                    $obj = $extension->getClass($param['subtype']);
                } else if (isset($param["default"]) && is_object($param["default"])) {
                    $obj = $param["default"];
                } else {
                    continue;
                }
                if (is_object($obj)) {
                    $code.= $obj->ifConditionStart();
                }
            }
        }
        
        return $code;
    }

    function ifConditionEnd($extension = false)
    {
        $code = "";
        
        if ($extension) { 
            $params = $this->params;
            $params[] = $this->returns;
            
            $params = array_reverse($params);
            
            foreach ($params as $param) {
                if ($param["type"] == "resource" && isset($param['subtype'])) {
                    $obj = $extension->getResource($param['subtype']);
                } else if ($param["type"] == "object" && isset($param['subtype'])) {
                   $obj = $extension->getClass($param['subtype']);
                } else if (isset($param["default"]) && is_object($param["default"])) {
                    $obj = $param["default"];
                } else {
                    continue;
                }
                if (is_object($obj)) {
                    $code.= $obj->ifConditionEnd();
                }
            }
        }

        $code .= parent::ifConditionEnd();

        return $code;
    }

    /**
     * Return minimal PHP version required to support the requested features
     *
     * @return  string  version string
     */
    function minPhpVersion()
    {
        // return by reference only exist in 5.1 and above
        if (isset($this->returns["byRef"])) {
            return "5.1.0rc1";
        }

		// default: 4.0
        return "4.0.0"; // TODO test for real lower bound 
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
