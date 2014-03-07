<?php

/*
 * This file is part of asciidia
 * Copyright (c) by Harald Lapp <harald@octris.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This script can be found at:
 * https://github.com/aurora/asciidia
 */

/*
 * The asciidia EBNF parser is -- in creating of the syntax-tree -- inspired 
 * by the EBNF parser of Vincent Tscherter: http://karmin.ch/ebnf/index
 */

// definition      =
// termination     ;
// alternation     |
// concatenation   ,
// option          [ ... ]
// repetition      { ... }
// grouping        ( ... )
// terminal string " ... "
// terminal string ' ... '

namespace asciidia\plugins {
    /**
     * Class for creating railroad-/syntax-diagrams from an EBNF.
     *
     * @octdoc      c:libs/ebnf
     * @copyright   copyright (c) 2011-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class ebnf extends \asciidia\plugin
    /**/
    {
        /**
         * Parser tokens.
         * 
         * @octdoc  d:ebnf/T_COMMENT, T_OPERATOR, T_LITERAL, T_IDENTIFIER, T_WHITESPACE, T_CONCATENATION
         */
        const T_COMMENT       = 0;
        const T_OPERATOR      = 1;
        const T_LITERAL       = 2;
        const T_IDENTIFIER    = 3;
        const T_WHITESPACE    = 4;
        const T_CONCATENATION = 5;
        /**/

        /**
         * Parser patterns.
         *
         * @octdoc  v:ebnf/$patterns
         * @type    array
         */
        protected static $patterns = array(
            self::T_COMMENT       => '\(\*.*?\*\)',
            self::T_OPERATOR      => '[=;\{\}\(\)\|\[\]]',
            self::T_LITERAL       => "(?:(?:\"(?:\\\\\"|[^\"])*\")|(?:\'(?:\\\\\'|[^\'])*\'))",
            self::T_IDENTIFIER    => '([a-zA-Z0-9_-]+|\<[a-zA-Z0-9_-]+\>)',
            self::T_WHITESPACE    => '\s+',
            self::T_CONCATENATION => ','
        );
        /**/
    
        /**
         * Token names.
         *
         * @octdoc  v:ebnf/$token_names
         * @type    array
         */
        protected static $token_names = array(
            self::T_COMMENT       => 'T_COMMENT',
            self::T_OPERATOR      => 'T_OPERATOR',
            self::T_LITERAL       => 'T_LITERAL',
            self::T_IDENTIFIER    => 'T_IDENTIFIER',
            self::T_WHITESPACE    => 'T_WHITESPACE',
            self::T_CONCATENATION => 'T_CONCATENATION'
        );
        /**/
    
        /**
         * Method to present a parse error. Application will be exit immediately
         * after presenting the error.
         *
         * @octdoc  m:ebnf/error
         * @param   string      $msg                Error message to print.
         * @param   array       $args               Optional arguments for error message.
         */
        protected function error($msg, array $args = array())
        /**/
        {
            vprintf($msg . "\n", $args);
            exit(1);
        }
    
        /**
         * EBNF tokenizer.
         *
         * @octdoc  m:ebnf/tokenize
         * @param   string      $in                 EBNF to tokenize.
         * @return  array                           EBNF tokens.
         */
        protected function tokenize($in)
        /**/
        {
            $out = array();
            $in  = stripslashes($in);

            $line = 1;

            while (strlen($in) > 0) {
                foreach (self::$patterns as $token => $regexp) {
                    if (preg_match('/^(' . $regexp . ')/', $in, $m)) {
                        if (in_array($token, array(self::T_WHITESPACE, self::T_COMMENT, self::T_CONCATENATION))) {
                            // spaces between tokens, comments and concatenation token are ignored but used for calculating line number
                            $line += substr_count($m[1], "\n");
                        } else {
                            $out[] = array(
                                'token' => $token,
                                'value' => $m[1],
                                'line'  => $line
                            );
                        }

                        $in = substr($in, strlen($m[1]));
                        continue 2;
                    }
                }
            
                $this->error('parse error in line %d at "%s"', array($line, $in));
            }

            return $out;
        }
    
        /**
         * Validate a specified token.
         *
         * @octdoc  m:ebnf/chkToken
         * @param   mixed       $token          Token to validate.
         * @param   int         $type           Type of token that is expected.
         * @param   mixed       $value          Optional expected value.
         * @return  bool                        Returns true if token is valid, otherwise false is returned.
         */
        protected function chkToken($token, $type, $value = null)
        /**/
        {
            return ($token
                    ? ((is_null($type) || $token['token'] == $type) && 
                        (is_null($value) || 
                        (is_array($value)
                            ? in_array($token['value'], $value)
                            : $token['value'] === $value)))
                    : false);
        }
    
        /**
         * Remove a token from stack.
         *
         * @octdoc  m:ebnf/eatToken
         * @param   array       $tokens         Token stack.
         * @return  bool                        Returns true if token was removed from stack successfully.
         */
        protected function eatToken(&$tokens)
        /**/
        {
            return (is_array($tokens)
                    ? !!array_shift($tokens)
                    : false);
        }
    
        /**
         * Return next token from stack.
         *
         * @octdoc  m:ebnf/getToken
         * @param   array       $tokens         Token stack.
         * @param   int         $type           Optional type of token that is expected.
         * @param   mixed       $value          Optional expected value.
         * @param   bool        $eat            Optional flag whether to remove token from stack.
         * @return  array|bool                  Token or false, if expectation where not fulfilled.
         */
        protected function getToken(&$tokens, $type = null, $value = null, $eat = true)
        /**/
        {
            if (($return = is_array($tokens))) {
                if ($eat) {
                    $return = array_shift($tokens);
                } else {
                    $return = current($tokens);
                }

                if (!$this->chkToken($return, $type, $value)) {
                    $return = false;
                }
            }
        
            return $return;   
        }
    
        /**
         * Parse EBNF production.
         *
         * @octdoc  m:ebnf/parseProd
         * @param   DOMDocument $dom         Document to add elements to.
         * @param   array       $tokens         Token stack.
         * @param   array       $current        Current token.
         * @return  array                       Elements to add to syntax.
         */
        protected function parseProd(DOMDocument $dom, &$tokens, array $current)
        /**/
        {
            $return = $dom->createElement('production');
            $return->setAttribute('name', $current['value']);
        
            if (!($token = $this->getToken($tokens, self::T_OPERATOR, '='))) {
                $this->error('identifier must be followed by "=" in line %d', array($current['line']));
            }
        
            $line = $token['line'];
        
            $return->appendChild($this->parseExpr($dom, $tokens, $token));
        
            if (!($token = $this->getToken($tokens, self::T_OPERATOR, ';'))) {
                $this->error('production must end with ";" in line %d', array($line));
            }
        
            return $return;
        }
    
        /**
         * Parse EBNF expression.
         *
         * @octdoc  m:ebnf/parseExpr
         * @param   DOMDocument $dom         Document to add elements to.
         * @param   array       $tokens         Token stack.
         * @param   array       $token          Current token.
         * @return  array                       Elements to add to syntax.
         */
        protected function parseExpr(DOMDocument $dom, &$tokens, array $token)
        /**/
        {
            $return = $dom->createElement('expression');
        
            do {
                $return->appendChild($this->parseTerm($dom, $tokens, $token));
            } while (($token = $this->getToken($tokens, self::T_OPERATOR, '|', false)) && $this->eatToken($tokens));
        
            return $return;
        }

        /**
         * Parse EBNF term.
         *
         * @octdoc  m:ebnf/parseTerm
         * @param   DOMDocument $dom         Document to add elements to.
         * @param   array       $tokens         Token stack.
         * @param   array       $token          Current token.
         * @return  array                       Elements to add to syntax.
         */
        public function parseTerm(DOMDocument $dom, &$tokens, array $token)
        /**/
        {
            $return = $dom->createElement('term');
        
            do {
                $return->appendChild($this->parseFact($dom, $tokens, $token));
            } while (!$this->getToken($tokens, null, array(';', '=', '|', ')', ']', '}'), false)); 
        
            return $return;
        }
    
        /**
         * Parse EBNF factor.
         *
         * @octdoc  m:ebnf/parseFact
         * @param   DOMDocument $dom         Document to add elements to.
         * @param   array       $tokens         Token stack.
         * @param   array       $current        Current token.
         * @return  array                       Elements to add to syntax.
         */
        public function parseFact(DOMDocument $dom, &$tokens, array $current)
        /**/
        {
            if (!($token = $this->getToken($tokens))) {
                $this->error('unexpected end in line %d', array($current['line']));
            } else {
                if ($this->chkToken($token, self::T_IDENTIFIER)) {
                    $return = $dom->createElement('identifier');
                    $return->setAttribute('value', $token['value']);
                } elseif ($this->chkToken($token, self::T_LITERAL)) {
                    $return = $dom->createElement('literal');
                    $return->setAttribute('value', stripcslashes(substr($token['value'], 1, -1)));
                } elseif ($this->chkToken($token, self::T_OPERATOR, '(')) {
                    $return = $this->parseExpr($dom, $tokens, $token);
            
                    if (!$this->getToken($tokens, self::T_OPERATOR, ')')) {
                        $this->error('group must end with ")"');
                    }
                } elseif ($this->chkToken($token, self::T_OPERATOR, '[')) {
                    $return = $dom->createElement('option');
                    $return->appendChild($this->parseExpr($dom, $tokens, $token));

                    if (!$this->getToken($tokens, self::T_OPERATOR, ']')) {
                        $this->error('option must end with "]"');
                    }
                } elseif ($this->chkToken($token, self::T_OPERATOR, '{')) {
                    $return = $dom->createElement('repetition');
                    $return->appendChild($this->parseExpr($dom, $tokens, $token));

                    if (!$this->getToken($tokens, self::T_OPERATOR, '}')) {
                        $this->error('loop must end with "}"');
                    }
                } else {
                    $this->error(
                        'unexpected token "%s" in line %d: %s', 
                        array(
                            self::$token_names[$token['token']],
                            $token['line'],
                            $token['value']
                        )
                    );
                }
            }
        
            return $return;
        }
    
        /**
         * Render syntax to imagemagick MVG commands.
         *
         * @octdoc  m:ebnf/render
         * @param   DOMNode     $node           Node to render.
         */
        protected function render(\DOMNode $node)
        /**/
        {
            $render = function(\DOMNode $node, \asciidia\context $context, $l2r = true) use (&$render) {
                // process node
                switch ($node->nodeName) {
                case 'syntax':
                    // first determine longest production name
                    $w = 0;
            
                    $child = $node->firstChild;
                    while ($child) {
                        $w = max($w, strlen($child->getAttribute('name')) + 1);
                    
                        $child = $child->nextSibling;
                    }
                
                    // render production
                    $max_th = $th = 0;
                    $max_tw = $tw = 0;
                
                    $twh = array();
    
                    $child = $node->firstChild;
                    while ($child) {
                        $context->translate(0, $th);

                        $ctx = $context->addContext();
                        $ctx->drawText(1, 0, $child->getAttribute('name'));
                        $ctx->drawMarker(0, 1, 'o', false, true, false, false);
                        $ctx->drawLine(0.5, 1, $w + 1, 1);

                        $ctx = $context->addContext();
                        $ctx->translate($w + 1, 0);

                        $render($child, $ctx, $l2r);

                        list($tw, $th) = $ctx->getSize(true);

                        $twh[] = array('h' => $max_th, 'w' => $tw);
                    
                        $max_th += $th;
                        $max_tw = max($max_tw, $tw);
                    
                        $child = $child->nextSibling;
                    }
                
                    $max_th -= $th;
                    $context->translate(0, -$max_th);

                    foreach ($twh as $tmp) {
                        $context->drawLine($tmp['w'] - 1, $tmp['h'] + 1, $max_tw + 1, $tmp['h'] + 1, 1);
                        $context->drawLine($max_tw + 1, $tmp['h'] + 1, $max_tw + 4, $tmp['h'] + 1);
                        $context->drawMarker($max_tw + 4, $tmp['h'] + 1, '+', true, false, true, false);
                    }
                
                    break;
                case 'production':
                    $child = $node->firstChild;
                    while ($child) {
                        $render($child, $context, $l2r);

                        $child = $child->nextSibling;
                    }
                    break;
                case 'expression':
                    $indent = (int)($node->childNodes->length > 1) * 3;
                    $max_th = $th = 0;
                    $max_tw = $tw = 0;
    
                    $twh = array();
    
                    if ($indent > 0) $context->translate($indent, 0);
            
                    $child = $node->firstChild;
                    while ($child) {
                        $context->translate(0, $th);
                    
                        $ctx = $context->addContext();
                    
                        $render($child, $ctx, $l2r);
                    
                        list($tw, $th) = $ctx->getSize(true);
                    
                        $twh[] = array('h' => $max_th, 'w' => $tw);
                    
                        $max_th += $th;
                        $max_tw = max($max_tw, $tw);
                    
                        $child = $child->nextSibling;
                    }

                    if ($indent > 0) {
                        $max_th -= $th;

                        $context->translate(-$indent, -$max_th);

                        foreach ($twh as $tmp) {
                            // draw pathes here
                            if ($tmp['h'] == 0) {
                                $context->drawLine(0, 1, 3, 1);
                                $context->drawLine($tmp['w'] + $indent - 1, 1, $max_tw + $indent + 2, 1);
                            } else {
                                $context->drawPath(
                                    array(
                                        array(0, 1), array(1, 1), array(1, $tmp['h'] + 1), array(3, $tmp['h'] + 1)
                                    ),
                                    false, true
                                );
                                $context->drawPath(
                                    array(
                                        array($tmp['w'] + $indent - 1, $tmp['h'] + 1), 
                                        array($max_tw + $indent + 1, $tmp['h'] + 1), 
                                        array($max_tw + $indent + 1, 1), 
                                        array($max_tw + $indent + 2, 1)
                                    ),
                                    false, true
                                );
                            }
                        }
                    }
                    break;
                case 'term':
                    $tw = 0;
            
                    $child = ($l2r ? $node->firstChild : $node->lastChild);
                    while ($child) {
                        $ctx = $context->addContext();
                        $ctx->translate($tw, 0);
                    
                        $render($child, $ctx, $l2r);
                    
                        list($tw, ) = $ctx->getSize(true);

                        if ($child = ($l2r ? $child->nextSibling : $child->previousSibling)) {
                            $context->drawLine($tw - 1, 1, $tw + 2, 1); //, ($l2r ? 1 : -1));
                            $tw += (int)(!$l2r) + 1;
                        }
                    }
                    break;
                case 'identifier':
                case 'literal':
                    $text = $node->getAttribute('value');
                    $len  = strlen($text);

                    $ctx = $context->addContext();
                    $ctx->drawLabel((int)$l2r, 0, $text, ($node->nodeName == 'identifier'));

                    if ($l2r) {
                        $ctx->drawLine(0, 1, 0, 1, 1);
                    } else {
                        list($tw, ) = $ctx->getSize(true);
                        $ctx->drawLine($tw, 1, $tw, 1, -1);
                    }

                    break;
                case 'repetition':
                    $ctx = $context->addContext();
                    $ctx->translate(3, 2);
                
                    $child = $node->firstChild;
                    while ($child) {
                        $render($child, $ctx, !$l2r);
            
                        $child = $child->nextSibling;
                    }
                
                    list($tw, $th) = $ctx->getSize(true);
                
                    $twf = ($tw + 3) / 2;
                
                    $context->drawLine(0, 1, $twf, 1, ($l2r ? 1 : -1));
                    $context->drawLine($twf, 1, $tw + 2, 1);
                    $context->drawPath(
                        array(
                            array(3, 3), array(1, 3), array(1, 1), array(3, 1)
                        ),
                        false, true
                    );
                    $context->drawPath(
                        array(
                            array($tw - 1, 1), array($tw + 1, 1), array($tw + 1, 3), array($tw - 1, 3)
                        ),
                        false, true
                    );
                
                    break;
                }
            };
        
            $render($node, $this->getContext());
        }
    
        /**
         * Parse a EBNF and convert it to imagemagick commands.
         *
         * @octdoc  m:ebnf/parse
         * @param   string      $diagram        ASCII Diagram to parse.
         * @return  string                      Imagemagick commands to draw diagram.
         */
        public function parse($diagram)
        /**/
        {
            // parse EBNF and create a syntax-tree of it.
            $tokens = $this->tokenize($diagram);
            $dom    = new \DOMDocument();
            $syntax = $dom->appendChild($dom->createElement('syntax'));
        
            // if (!($token = $this->getToken($tokens, self::T_OPERATOR, '{'))) {
            //     // $this->error('EBNF must start with "{"');
            // }

            while (count($tokens) > 0 && ($token = $this->getToken($tokens, self::T_IDENTIFIER))) {
                $syntax->appendChild($this->parseProd($dom, $tokens, $token));
            }
        
            // if (count($tokens) > 1 || $this->chkToken($token, self::T_OPERATOR, '}')) {
            //     // $this->error('EBNF must end with "}"');
            // }

            // render syntax and return it's MVG commands
            $this->render($syntax);

            return $this->getCommands();
        }
    }
}
