<?php
require_once "ProtoLexer.php";
require_once "ProtoParser.php";

$lex    = new CodeGen_PECL_Tools_ProtoLexer("boolx foobar(int foo, string bar);");
$parser = new CodeGen_PECL_Tools_ProtoParser();
while ($lex->yylex()) {
    printf("LEX: %d:%s\n", $lex->token, $lex->value);
    $parser->doParse($lex->token, $lex->value);
}
$parser->doParse(0, 0);

$parser->dump();
?>
