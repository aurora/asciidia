<?php

/**
 * Class for creating "nice"-looking directory-trees from ASCII representation.
 *
 * @octdoc      c:libs/tree
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class tree extends asciidia
/**/
{
    /**
     * Draw a diagram.
     *
     * @octdoc  m:diagram/draw
     * @param   int         $x                  X-position of cell to draw.
     * @param   int         $y                  Y-position of cell to draw.
     * @param   string      $ch                 Character to draw.
     * @param   callback    $get_surrounding    Callback to determine surrounding characters.
     * @return  string                          Imagemagick draw commands.
     */
    public function draw($x, $y, $ch, $get_surrounding)
    /**/
    {
        list($a, $r, $b, $l) = $get_surrounding();
        
        $output = array();
        
        if ($ch == '+' && ($a == '|' || $r == '-' || $b == '|' || $l == '-' || $b == '+' || $a == '+')) {
            if ($l == '-' || $r == '-') {
                $output[] = sprintf(
                    "line   %d,%d %d,%d",
                    ($l == '-' ? $x * $this->xs : $x * $this->xs + $this->xf),
                    $y * $this->ys + $this->yf,
                    ($r == '-' ? ($x + 1) * $this->xs - 1 : $x * $this->xs + $this->xf), 
                    $y * $this->ys + $this->yf
                );
            }
            
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs + $this->xf,
                $y * $this->ys,
                $x * $this->xs + $this->xf,
                (($b == '|' || $b == '+') ? ($y + 1) * $this->ys - 1 : $y * $this->ys + $this->yf)
            );
        } elseif ($ch == '-') {
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs, $y * $this->ys + $this->yf,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf
            );
        } elseif ($ch == '|') {
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs + $this->xf, $y * $this->ys,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1
            );
        } elseif ($ch != ' ') {
            // draw text
            $output[] = sprintf(
                "text %d,%d '%s'",
                $x * $this->xs,
                ($y + 1) * $this->ys - ($this->yf / 2),
                addcslashes($ch, "'")
            );
        }
        
        return $output;
    }
    
    /**
     * Return recursive directory ASCII tree for a specified path.
     *
     * @octdoc  m:tree/getTree
     * @return  string      $path           Path to return directory tree for.
     */
    public function getTree($path)
    /**/
    {
        $path = rtrim($path, '/');
        $out  = array(basename($path));
        
        $rglob = function($path, $struct = '') use (&$rglob, &$out) {
            $dirs = glob($path . '/*', GLOB_ONLYDIR);
            
            for ($i = 0, $cnt = count($dirs); $i < $cnt; ++$i) {
                if (!is_dir($dirs[$i]) || substr($name = basename($dirs[$i]), 0, 1) == '.') {
                    continue;
                }
                
                $out[] = $struct . '+-' . $name;
                
                $rglob($dirs[$i], $struct . ($i == $cnt - 1 ? '  ' : '| '));
            }
        };
        
        $rglob($path);

        return implode("\n", $out);
    }
}
