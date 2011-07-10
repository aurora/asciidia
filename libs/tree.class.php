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
}
