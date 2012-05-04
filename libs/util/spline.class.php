<?php

/*
 * This class is based on the works of Oleg V. Polikarpotchkin and Peter Lee and
 * was published in 2009 in an article at codeproject.com. The source code was
 * published under the terms of the "Code Project Open License (CPOL) 1.02". The
 * article including the source was published at:
 *
 * http://www.codeproject.com/Articles/31859/Draw-a-Smooth-Curve-through-a-Set-of-2D-Points-wit
 *
 * The CPOL allows me to port the code to PHP and include it with asciidia.
 */

/**
 * Calculate control points for a bezier curve required for drawing a spline
 *
 * @octdoc  c:util/spline
 */
class spline 
/**/
{
    /**
     * Solves a tridiagonal system for one of coordinates (x or y) of first Bezier control points.
     *
     * @octdoc  m:spline/getFirstControlPoints
     */
    protected static function getFirstControlPoints(array $points)
    /**/
    {
        $b   = 2.0;
        $x   = array($points[0] / $b);  // Solution vector
        $tmp = array();                 // Temp workspace

        for ($i = 1, $cnt = count($points); $i < $cnt; ++$i) {
            $tmp[$i] = 1 / $b;
            $b       = ($i < $cnt - 1 ? 4.0 : 3.5) - $tmp[$i];
            $x[$i]   = ($points[$i] - $x[$i - 1]) / $b;
        }

        for ($i = 1; $i < $cnt; ++$i) {
            $x[$cnt - $i - 1] -= $tmp[$cnt - $i] * $x[$cnt - $i];
        }

        return $x;
    }

    /**
     * Calculate control points from specified points.
     *
     * @octdoc  m:spline/getControlPoints
     * @param   array               $points                 Spline points.
     * @return  array                                       Calculated control points.
     */
    public static function getControlPoints(array $points)
    /**/
    {
        $tmp = array(
            'x' => array($points[0][0] + $points[1][0] * 2),
            'y' => array($points[0][1] + $points[1][1] * 2)
        );

        for ($i = 1, $cnt = count($points); $i < $cnt - 2; ++$i) {
            $tmp['x'][] = $points[$i][0] * 4 + $points[$i + 1][0] * 2;
            $tmp['y'][] = $points[$i][1] * 4 + $points[$i + 1][1] * 2;
        }

        $tmp['x'][] = ($points[$cnt - 2][0] * 8 + $points[$cnt - 1][0]) / 2;
        $tmp['y'][] = ($points[$cnt - 2][1] * 8 + $points[$cnt - 1][1]) / 2;

        $x = self::getFirstControlPoints($tmp['x']);
        $y = self::getFirstControlPoints($tmp['y']);

        $first  = array();
        $second = array();

        for ($i = 0; $i < $cnt - 1; ++$i) {
            $first[$i] = array($x[$i], $y[$i]);

            if ($i < $cnt - 2) {
                $second[$i] = array(
                    $points[$i + 1][0] * 2 - $x[$i + 1],
                    $points[$i + 1][1] * 2 - $y[$i + 1]
                );
            } else {
                $second[$i] = array(
                    ($points[$cnt - 1][0] + $x[$cnt - 2]) / 2,
                    ($points[$cnt - 1][1] + $y[$cnt - 2]) / 2
                );
            }
        }

        return array($first, $second);
    }
}
