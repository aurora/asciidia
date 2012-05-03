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

require_once(__DIR__ . '/../plugin.class.php');

/*
 * This is a port of my identicon shell script you can find at:
 * https://github.com/aurora/identicon/
 *
 * ... which in turn is a re-implementation
 * of the "PHP identicons" script you can find at:
 * http://identicons.sourceforge.net/
 */

/**
 * Class for creating identicons.
 *
 * @octdoc      c:plugin/identicon
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class identicon extends plugin
/**/
{
    /**
     * Border shapes.
     *
     * @octdoc  v:identicon/$border
     * @var     array
     */
    protected static $border = array(
        // triangle
        array(array(0.5, 1), array(1, 0), array(1, 1)),
        // parallelogram
        array(array(0.5, 0), array(1, 0), array(0.5, 1), array(0, 1)),
        // mouse ears
        array(array(0.5, 0), array(1, 0), array(1, 1), array(0.5, 1), array(1, 0.5)),
        // ribbon
        array(array(0, 0.5), array(0.5, 0), array(1, 0.5), array(0.5, 1), array(0.5, 0.5)),
        // sails
        array(array(0, 0.5), array(1, 0), array(1, 1), array(0, 1), array(1, 0.5)),
        // fins
        array(array(1, 0), array(1, 1), array(0.5, 1), array(1, 0.5), array(0.5, 0.5)),
        // beak
        array(array(0, 0), array(1, 0), array(1, 0.5), array(0, 0), array(0.5, 1), array(0, 1)),
        // chevron
        array(array(0, 0), array(0.5, 0), array(1, 0.5), array(0.5, 1), array(0, 1), array(0.5, 0.5)),
        // fish
        array(array(0.5, 0), array(0.5, 0.5), array(1, 0.5), array(1, 1), array(0.5, 1), array(0.5, 0.5), array(0, 0.5)),
        // kite
        array(array(0, 0), array(1, 0), array(0.5, 0.5), array(1, 0.5), array(0.5, 1), array(0.5, 0.5), array(0, 1)),
        // trough
        array(array(0, 0.5), array(0.5, 1), array(1, 0.5), array(0.5, 0), array(1, 0), array(1, 1), array(0, 1)),
        // rays
        array(array(0.5, 0), array(1, 0), array(1, 1), array(0.5, 1), array(1, 0.75), array(0.5, 0.5), array(1, 0.25)),
        // double rhombus
        array(array(0, 0.5), array(0.5, 0), array(0.5, 0.5), array(1, 0), array(1, 0.5), array(0.5, 1), array(0.5, 0.5), array(0, 1)),
        // crown
        array(array(0, 0), array(1, 0), array(1, 1), array(0, 1), array(1, 0.5), array(0.5, 0.25), array(0.5, 0.75), array(0, 0.5), array(0.5, 0.25)),
        // radioactive
        array(array(0, 0.5), array(0.5, 0.5), array(0.5, 0), array(1, 0), array(0.5, 0.5), array(1, 0.5), array(0.5, 1), array(0.5, 0.5), array(0, 1)),
        // tiles
        array(array(0, 0), array(1, 0), array(0.5, 0.5), array(0.5, 0), array(0, 0.5), array(1, 0.5), array(0.5, 1), array(0.5, 0.5), array(0, 1))
    );
    /**/
    
    /**
     * Center shapes.
     *
     * @octdoc  v:identicon/$center
     * @var     array
     */
    protected static $center = array(
        // empty
        array(),
        // fill
        array(array(0, 0), array(1, 0), array(1, 1), array(0, 1)),
        // diamond
        array(array(0.5, 0), array(1, 0.5), array(0.5, 1), array(0, 0.5)),
        // reverse diamond
        array(array(0, 0), array(1, 0), array(1, 1), array(0, 1), array(0, 0.5), array(0.5, 1), array(1, 0.5), array(0.5, 0), array(0, 0.5)),
        // cross
        array(array(0.25, 0), array(0.75, 0), array(0.5, 0.5), array(1, 0.25), array(1, 0.75), array(0.5, 0.5), array(0.75, 1), array(0.25, 1), array(0.5, 0.5), array(0, 0.75), array(0, 0.25), array(0.5, 0.5)),
        // morning star
        array(array(0, 0), array(0.5, 0.25), array(1, 0), array(0.75, 0.5), array(1, 1), array(0.5, 0.75), array(0, 1), array(0.25, 0.5)),
        // small square
        array(array(0.33, 0.33), array(0.67, 0.33), array(0.67, 0.67), array(0.33, 0.67)),
        // checkerboard
        array(
            array(0, 0), array(0.33, 0), array(0.33, 0.33), array(0.66, 0.33), array(0.67, 0), array(1, 0), array(1, 0.33), array(0.67, 0.33),
            array(0.67, 0.67), array(1, 0.67), array(1, 1), array(0.67, 1), array(0.67, 0.67), array(0.33, 0.67), array(0.33, 1), array(0, 1),
            array(0, 0.67), array(0.33, 0.67), array(0.33, 0.33), array(0, 0.33)
        )
    );
    /**/
    
    /**
     * Internal size of each sprite.
     *
     * @octdoc  v:identicon/$spriteZ
     * @var     int
     */
    protected $spriteZ = 128;
    /**/

    /**
     * Overwrite parent method to additional check if loaded content is a valid
     * hash to use with this plugin.
     *
     * @octdoc  m:identicon/loadFile
     * @param   string      $name               Name of file to load.
     * @return  array                           Status information.
     */
    public function loadFile($name)
    /**/
    {
        list($status, $msg, $content) = parent::loadFile($name);
        
        if ($status) {
            $content = trim($content);
            
            if (!preg_match('/^[0-9a-fA-F]{17,}$/', $content)) {
                $status  = false;
                $msg     = 'no valid hash provided';
                $content = '';
            }
        }
        
        return array($status, $msg, $content);
    }

    /**
     * Draw border sprite.
     *
     * @octdoc  m:identicon/drawBorder
     * @param   context     $context        Drawing context.
     * @param   int         $shape          Shape to return points of.
     * @param   array       $rgb            RGB color values for shape.
     * @param   float       $rotation       Rotation angle.
     * @param   float       $tx             X-translation value.
     * @param   float       $ty             Y-translation value.
     */
    public function drawBorder(context $context, $shape, array $rgb, $rotation, $tx, $ty)
    /**/
    {
        $points       = self::$border[($shape <= 15 ? $shape : 15)];
        $spriteZCoord = $this->spriteZ - 1;

        switch ($rotation) {
        case 0:
            $xOffs = 0; $yOffs = 0;
            break;
        case 90:
            $xOffs = 0; $yOffs = -$spriteZCoord;
            break;
        case 180:
            $xOffs = -$spriteZCoord; $yOffs = -$spriteZCoord;
            break;
        case 270:
            $xOffs = -$spriteZCoord; $yOffs = 0;
            break;
        }

        // create polygon with the applied ratio
        $poly = array();
        
        foreach ($points as $p) {
            $poly[] = sprintf('%f,%f', $p[0] * $spriteZCoord + $xOffs, $p[1] * $spriteZCoord + $yOffs);
        }

        $ctx = $context->addContext();
        $ctx->translate($tx, $ty);
        $ctx->addCommand(sprintf('rotate %f', $rotation));
        $ctx->addCommand(vsprintf('fill rgb(%d,%d,%d)', $rgb));
        $ctx->addCommand(sprintf("path 'M %s Z'", implode(' ', $poly)));
    }

    /**
     * Draw center sprite.
     *
     * @octdoc  m:identicon/drawCenter
     * @param   context     $context        Drawing context.
     * @param   int         $shape          Shape to return points of.
     * @param   array       $frgb           Foreground RGB color values.
     * @param   array       $brgb           Background RGB color values.
     * @param   float       $tx             X-translation value.
     * @param   float       $ty             Y-translation value.
     */
    public function drawCenter(context $context, $shape, array $frgb, array $brgb, $tx, $ty)
    /**/
    {
        $points       = self::$center[($shape <= 7 ? $shape : 7)];
        $spriteZCoord = $this->spriteZ - 1;

        // create polygon with the applied ratio
        if (count($points) > 0) {
            $poly = array();
        
            foreach ($points as $p) {
                $poly[] = sprintf('%f,%f', $p[0] * $spriteZCoord, $p[1] * $spriteZCoord);
            }

            $ctx = $context->addContext();
            $ctx->translate($tx, $ty);
            $ctx->addCommand(vsprintf('fill rgb(%d,%d,%d)', $brgb));
            $ctx->addCommand(sprintf('rectangle 0,0 %f,%f', $spriteZCoord, $spriteZCoord));
            $ctx->addCommand(vsprintf('fill rgb(%d,%d,%d)', $frgb));
            $ctx->addCommand(sprintf("path 'M %s Z'", implode(' ', $poly)));
        }
    }

    /**
     * Process hash value.
     *
     * @octdoc  m:identicon/parse
     * @param   string      $hash           Hash values to create identicons from.
     * @return  string                      Imagemagick commands to draw diagram.
     */
    public function parse($hash)
    /**/
    {
        // initialization
        $csh = intval(substr($hash, 0, 1), 16);         // corner sprite shape
        $ssh = intval(substr($hash, 1, 1), 16);         // side sprite shape
        $xsh = intval(substr($hash, 2, 1), 16) & 7;     // center sprite shape

        $cro = intval(substr($hash, 3, 1), 16) & 3;     // corner sprite rotation
        $sro = intval(substr($hash, 4, 1), 16) & 3;     // side sprite rotation
        $xbg = intval(substr($hash, 5, 1), 16) % 2;     // center sprite background

        $cfr = intval(substr($hash, 6, 2), 16);         // corner sprite foreground color
        $cfg = intval(substr($hash, 8, 2), 16);
        $cfb = intval(substr($hash, 10, 2), 16);

        $sfr = intval(substr($hash, 12, 2), 16);        // side sprite foreground color
        $sfg = intval(substr($hash, 14, 2), 16);
        $sfb = intval(substr($hash, 16, 2), 16);

        $context = $this->getContext();
        $context->xs = 1;
        $context->ys = 1;

        $context->setSize($this->spriteZ * 3, $this->spriteZ * 3);

        // generate corner sprites
        $ctx = $this->getContext();
        $ctx->addCommand('stroke transparent');
        
        $this->drawBorder($ctx, $csh, array($cfr, $cfg, $cfb), ($cro * 270) % 360, 0, 0);
        $this->drawBorder($ctx, $csh, array($cfr, $cfg, $cfb), ($cro * 270 + 90) % 360, $this->spriteZ * 2, 0);
        $this->drawBorder($ctx, $csh, array($cfr, $cfg, $cfb), ($cro * 270 + 180) % 360, $this->spriteZ * 2, $this->spriteZ * 2);
        $this->drawBorder($ctx, $csh, array($cfr, $cfg, $cfb), ($cro * 270 + 270) % 360, 0, $this->spriteZ * 2);

        // generate side sprites
        $this->drawBorder($ctx, $ssh, array($sfr, $sfg, $sfb), ($sro * 270) % 360, $this->spriteZ, 0);
        $this->drawBorder($ctx, $ssh, array($sfr, $sfg, $sfb), ($sro * 270 + 90) % 360, $this->spriteZ * 2, $this->spriteZ);
        $this->drawBorder($ctx, $ssh, array($sfr, $sfg, $sfb), ($sro * 270 + 180) % 360, $this->spriteZ, $this->spriteZ * 2);
        $this->drawBorder($ctx, $ssh, array($sfr, $sfg, $sfb), ($sro * 270 + 270) % 360, 0, $this->spriteZ);

        // generate center sprite
        $dr = abs($cfr - $sfr);
        $dg = abs($cfg - $sfg);
        $db = abs($cfb - $sfb);

        if ($xbg > 0 && ($dr > 127 || $dg > 127 || $db > 127)) {
            // make sure there's enough contrast before we use background color of side sprite
            $rgb = array($sfr, $sfg, $sfb);
        } else {
            $rgb = array(255, 255, 255);
        }

        $this->drawCenter($ctx, $xsh, array($cfr, $cfg, $cfb), $rgb, $this->spriteZ, $this->spriteZ);
        
        return $this->getCommands();
    }
}
