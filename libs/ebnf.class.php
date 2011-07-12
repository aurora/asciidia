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

/**
 * Class for creating railroad-/syntax-diagrams from an EBNF.
 *
 * @octdoc      c:libs/ebnf
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class ebnf extends diagram
/**/
{
    /**
     * Parser tokens.
     * 
     * @octdoc  d:ebnf/T_NOP, T_OPERATOR, T_LITERAL, T_IDENTIFIER, T_WHITESPACE
     */
    const T_NOP        = 0;
    const T_OPERATOR   = 1;
    const T_LITERAL    = 2;
    const T_IDENTIFIER = 3;
    const T_WHITESPACE = 4;
    const T_END        = 5;
    /**/
    
    /**
     * Parser patterns.
     *
     * @octdoc  v:ebnf/$patterns
     * @var     array
     */
    protected static $patterns = array(
        self::T_OPERATOR   => '[=\{\}\(\)\|\.\[\]]',
        self::T_LITERAL    => "([\"']).*?(?!\\\\)\\2",
        self::T_IDENTIFIER => '[a-zA-Z0-9_-]+',
        self::T_WHITESPACE => '\s+',
        self::T_END        => ';'
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
    public function chkToken($token, $type, $value = null)
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
     * Return next token from stack.
     *
     * @octdoc  m:ebnf/getToken
     * @param   array       $tokens         Token stack.
     * @param   int         $type           Optional type of token that is expected.
     * @param   mixed       $value          Optional expected value.
     * @param   bool        $push           Optional push token back on stack if it's not valid.
     * @return  array|bool                  Token or false, if expectation where not fulfilled.
     */
    protected function getToken(&$tokens, $type = null, $value = null, $push = false)
    /**/
    {
        if (($return = (is_array($tokens) && ($token = array_shift($tokens)))) && !is_null($type)) {
            if (!($return = $this->chkToken($token, $type, $value)) && $push) {
                array_unshift($tokens, $token);
            }
        }
        
        return $return;   
    }
    
    /**
     * Parse EBNF production.
     *
     * @octdoc  m:ebnf/parseProd
     * @param   array       $tokens         Token stack.
     * @param   string      $name           Name of production rule.
     * @return  array                       Elements to add to syntax.
     */
    protected function parseProd(&$tokens, $name)
    /**/
    {
        $return = array('name' => $name, 'children' => array());
        
        if (!($token = $this->getToken($tokens, self::T_OPERATOR, '='))) {
            $this->error('identifier must be followed by "="');
        }
        
        $return['children'][] = $this->parseExpr($syntax, $tokens);
        
        if (!($token = $this->getToken($tokens, self::T_OPERATOR, ';'))) {
            $this->error('production must end with ";"');
        }
        
        return $return;
    }
    
    /**
     * Parse EBNF expression.
     *
     * @octdoc  m:ebnf/parseExpr
     * @param   array       $tokens         Token stack.
     * @return  array                       Elements to add to syntax.
     */
    protected function parseExpr(&$tokens)
    /**/
    {
        $return = array('children' => array());

        do {
            $return['children'][] = $this->parseTerm($tokens);
        } while (($token = $this->getToken($tokens, self::T_OPERATOR, '|', true)));
        
        return $return;
    }

    /**
     * Parse EBNF term.
     *
     * @octdoc  m:ebnf/parseTerm
     * @param   array       $tokens         Token stack.
     * @return  array                       Elements to add to syntax.
     */
    public function parseTerm(&$tokens)
    /**/
    {
        $return = array('children' => array());
        
        do {
            $return['children'][] = $this->parseFact($tokens);
        } while (!$this->getToken($tokens, self::T_OPERATOR, array(';', '=', '|', ')', ']', '}')));
        
        return $return;
    }
    
    /**
     * Parse EBNF factor.
     *
     * @octdoc  m:ebnf/parseFact
     * @param   array       $tokens         Token stack.
     * @return  array                       Elements to add to syntax.
     */
    public function parseFact(&$tokens)
    /**/
    {
        if (!($token = $this->getToken($tokens))) {
            $this->error('unexpected end -- no more tokens');
        } elseif ($this->chkToken($token, T_IDENTIFIER)) {
            return array('value' => $token['value']);
        } elseif ($this->chkToken($token, T_LITERAL)) {
            return array('value' => stripcslashes(substr($token['value'], 1, -1)));
        } elseif ($this->chkToken($token, T_OPERATOR, '(')) {
            $return = $this->parseExpr($tokens);
            
            if (!$this->getToken($tokens, T_OPERATOR, ')')) {
                $this->error('group must end with ")"');
            }
            
            return $return;
        } elseif ($this->chkToken($token, T_OPERATOR, '[')) {
            $return = $this->parseExpr($tokens);

            if (!$this->getToken($tokens, T_OPERATOR, ']')) {
                $this->error('option must end with "]"');
            }

            return $return;
        } elseif ($this->chkToken($token, T_OPERATOR, '{')) {
            $return = $this->parseExpr($tokens);

            if (!$this->getToken($tokens, T_OPERATOR, '}')) {
                $this->error('loop must end with "}"');
            }

            return $return;
        } else {
            $this->error('unexpected token %d: "%s"', array_values($token));
        }
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
        $syntax = array('children' => array());
        
        if (!($token = $this->getToken($tokens, self::T_OPERATOR, '{'))) {
            $this->error('EBNF must start with "{"');
        }

        while (count($tokens) > 0 && ($token = $this->getToken($tokens, self::T_IDENTIFIER, null, true))) {
            $syntax['children'][] = $this->parseProd($tokens, $token['value']);
        }
        
        if (count($tokens) > 1 || $this->checkToken($token, self::T_OPERATOR, '}')) {
            $this->error('EBNF must end with "}"');
        }
        
        print_r($syntax);
        
        die;
        
        return parent::parse($diagram);
    }
}
