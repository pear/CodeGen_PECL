<?php
class CodeGen_PECL_Tools_ProtoLexer
{
    private $data;
    public $token;
    public $value;
    private $line;
    private $count;

    function __construct($data)
    {
        $this->data  = $data;
        $this->count = 0;
        $this->line  = 1;
    }


    private $_yy_state = 1;
    private $_yy_stack = array();

    function yylex()
    {
        return $this->{'yylex' . $this->_yy_state}();
    }

    function yypushstate($state)
    {
        array_push($this->_yy_stack, $this->_yy_state);
        $this->_yy_state = $state;
    }

    function yypopstate()
    {
        $this->_yy_state = array_pop($this->_yy_stack);
    }

    function yybegin($state)
    {
        $this->_yy_state = $state;
    }




    function yylex1()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 0,
              4 => 0,
              5 => 0,
              6 => 0,
              7 => 0,
              8 => 0,
              9 => 0,
              10 => 0,
              11 => 0,
              12 => 0,
              13 => 2,
              16 => 0,
              17 => 0,
            );
        if ($this->count >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/^([ \t\n]+)|^(\\()|^(\\))|^(\\[)|^(\\])|^(=)|^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)/";

        do {
            if (preg_match($yy_global_pattern, substr($this->data, $this->count), $yymatches)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        'an empty string.  Input "' . substr($this->data,
                        $this->count, 5) . '... state 1');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r1_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->count += strlen($this->value);
                    $this->line += substr_count("\n", $this->value);
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->count += strlen($this->value);
                    $this->line += substr_count("\n", $this->value);
                    if ($this->count >= strlen($this->data)) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                } else {                    $yy_yymore_patterns = array(
        1 => "^(\\()|^(\\))|^(\\[)|^(\\])|^(=)|^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        2 => "^(\\))|^(\\[)|^(\\])|^(=)|^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        3 => "^(\\[)|^(\\])|^(=)|^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        4 => "^(\\])|^(=)|^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        5 => "^(=)|^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        6 => "^(,)|^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        7 => "^(;)|^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        8 => "^(\\.\\.\\.)|^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        9 => "^([&@])|^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        10 => "^([_a-zA-Z][_a-zA-Z0-9]*)|^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        11 => "^(\"[^\"]*\"|'[^']*')|^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        12 => "^([0-9]*(\\.[0-9]+)?([eE][+-]?[0-9]+)?)|^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        13 => "^([+-]?[0-9]+)|^(0x[0-9a-fA-F]+)",
        16 => "^(0x[0-9a-fA-F]+)",
        17 => "",
    );

                    // yymore is needed
                    do {
                        if (!strlen($yy_yymore_patterns[$this->token])) {
                            throw new Exception('cannot do yymore for the last token');
                        }
                        if (preg_match($yy_yymore_patterns[$this->token],
                              substr($this->data, $this->count), $yymatches)) {
                            $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                            next($yymatches); // skip global match
                            $this->token = key($yymatches); // token number
                            $this->value = current($yymatches); // token value
                            $this->line = substr_count("\n", $this->value);
                        }
                    } while ($this->{'yy_r1_' . $this->token}() !== null);
                    // accept
                    $this->count += strlen($this->value);
                    $this->line += substr_count("\n", $this->value);
                    return true;
                }
            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->count]);
            }
            break;
        } while (true);
    } // end function

    function yy_r1_1($yy_subpatterns)
    {

	return false;
    }
    function yy_r1_2($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::PAR_OPEN;
    }
    function yy_r1_3($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::PAR_CLOSE;
    }
    function yy_r1_4($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::SQUARE_OPEN;
    }
    function yy_r1_5($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::SQUARE_CLOSE;
    }
    function yy_r1_6($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::EQ;
    }
    function yy_r1_7($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::COMMA;
    }
    function yy_r1_8($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::SEMICOLON;
    }
    function yy_r1_9($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::ELLIPSE;
    }
    function yy_r1_10($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::AMPERSAND;
    }
    function yy_r1_11($yy_subpatterns)
    {

	switch ($this->value) {
	case "void":
		$this->token = CodeGen_PECL_Tools_ProtoParser::VOID;
		break;
	case "bool": 	
    case "boolean":
	    $this->token = CodeGen_PECL_Tools_ProtoParser::BOOL;
		break;
	case "int": 
	case "integer":
	case "long":
		$this->token = CodeGen_PECL_Tools_ProtoParser::INT;
		break;
	case "float":
	case "double":
		$this->token = CodeGen_PECL_Tools_ProtoParser::FLOAT;
		break;
	case "string": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::STRING;
		break;
	case "array": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::ARRAY_;
		break;
	case "class":
	case "object":
		$this->token = CodeGen_PECL_Tools_ProtoParser::CLASS_;
		break;
	case "resource": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::RESOURCE;
		break;
	case "mixed": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::MIXED;
		break;
	case "callback": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::CALLBACK;
		break;
	case "stream": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::STREAM;
		break;
	case "true": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::TRUE_;
		break;
	case "false":
		$this->token = CodeGen_PECL_Tools_ProtoParser::FALSE_;
		break;
	case "null": 
		$this->token = CodeGen_PECL_Tools_ProtoParser::NULL_;
		break;
	default:
		$this->token = CodeGen_PECL_Tools_ProtoParser::NAME;
		break;
	}
    }
    function yy_r1_12($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::STRVAL;
    }
    function yy_r1_13($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::NUMVAL;
    }
    function yy_r1_16($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::NUMVAL;
    }
    function yy_r1_17($yy_subpatterns)
    {

	$this->token = CodeGen_PECL_Tools_ProtoParser::NUMVAL;
    }

}

