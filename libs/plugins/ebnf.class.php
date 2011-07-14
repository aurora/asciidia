<?php

/*
 * This file is part of asciidia
 * Copyright (C) 2011 by Harald Lapp <harald@octris.org>
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

// definition      =
// termination     ;
// alternation     |
// option          [ ... ]
// repetition      { ... }
// grouping        ( ... )
// terminal string " ... "
// terminal string ' ... '

require_once(__DIR__ . '/../plugin.class.php');

/**
 * Class for creating railroad-/syntax-diagrams from an EBNF.
 *
 * @octdoc      c:libs/ebnf
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class ebnf extends plugin
/**/
{
    /**
     * Parser tokens.
     * 
     * @octdoc  d:ebnf/T_NOP, T_OPERATOR, T_LITERAL, T_IDENTIFIER, T_WHITESPACE
     */
    const T_OPERATOR   = 1;
    const T_LITERAL    = 2;
    const T_IDENTIFIER = 3;
    const T_WHITESPACE = 4;
    /**/

    /**
     * Parser patterns.
     *
     * @octdoc  v:ebnf/$patterns
     * @var     array
     */
    protected static $patterns = array(
        self::T_OPERATOR   => '[=;\{\}\(\)\|\[\]]',
        self::T_LITERAL    => "([\"']).*?(?!\\\\)\\2",
        self::T_IDENTIFIER => '[a-zA-Z0-9_-]+',
        self::T_WHITESPACE => '\s+'
    );
    /**/
    
    /**
     * Token names.
     *
     * @octdoc  v:ebnf/$token_names
     * @var     array
     */
    protected static $token_names = array(
        self::T_OPERATOR   => 'T_OPERATOR',
        self::T_LITERAL    => 'T_LITERAL',
        self::T_IDENTIFIER => 'T_IDENTIFIER',
        self::T_WHITESPACE => 'T_WHITESPACE'
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
                    // spaces between tokens are ignored but used for calculating line number
                    if ($token == self::T_WHITESPACE) {
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
                ? ($token['token'] == $type && 
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

            if (!is_null($type) && !$this->chkToken($return, $type, $value)) {
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
        } while (($token = current($tokens)) && !in_array($token['value'], array(';', '=', '|', ')', ']', '}'))); 
    // } while (($this->getToken($tokens, self::T_OPERATOR, null, false)) && !in_array($token['value'], array(';', '=', '|', ')', ']', '}'))); 
         // && $this->eatToken($tokens));
        
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
     * Render syntax document to ASCII diagram.
     *
     * @octdoc  m:ebnf/render
     * @param   DOMNode     $node           Node to render.
     * @return  string                      Rendered ASCII diagram.
     */
    protected function render(DOMNode $node)
    /**/
    {
        return '';
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
        $tokens = $this->tokenize($diagram);
        $dom    = new DOMDocument();
        $syntax = $dom->createElement('syntax');
        
        if (!($token = $this->getToken($tokens, self::T_OPERATOR, '{'))) {
            $this->error('EBNF must start with "{"');
        }

        while (count($tokens) > 0 && ($token = $this->getToken($tokens, self::T_IDENTIFIER))) {
            $syntax->appendChild($this->parseProd($dom, $tokens, $token));
        }
        
        if (count($tokens) > 1 || $this->chkToken($token, self::T_OPERATOR, '}')) {
            $this->error('EBNF must end with "}"');
        }
        
        $diagram = $this->render($syntax);

        return parent::parse($diagram);
    }
}
