<?php

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Adds support for `TUPLE(some_expression, other_expression, ...)` in DQL.
 */
class TupleFunction extends FunctionNode {
    private $expr = [];

    public function getSql(SqlWalker $sqlWalker): string {
        $prefix = \count($this->expr) <= 1 ? 'ROW' : '';
        $expr = array_map([$sqlWalker, 'walkArithmeticPrimary'], $this->expr);

        return sprintf('%s(%s)', $prefix, implode(', ', $expr));
    }

    public function parse(Parser $parser): void {
        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        while (!$lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS)) {
            if ($this->expr) {
                $parser->match(Lexer::T_COMMA);
            }

            $this->expr[] = $parser->ArithmeticPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
