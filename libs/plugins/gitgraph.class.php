<?php

/*
 * This file is part of asciidia
 * Copyright (C) 2012 by Harald Lapp <harald@octris.org>
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

/**
 * Main application class.
 *
 * @octdoc      c:libs/gitgraph
 * @copyright   copyright (c) 2012 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class gitgraph extends plugin
/**/
{
    /**
     * Width of a bar.
     *
     * @octdoc  p:gitgraph/$bar_width
     * @var     int
     */
    protected $bar_width = 10;
    /**/
    
    /**
     * Start date as UNIX timestamp.
     *
     * @octdoc  p:gitgraph/$start
     * @var     int
     */
    protected $start;
    /**/
    
    /**
     * End date as UNIX timestamp.
     *
     * @octdoc  p:gitgraph/$end
     * @var     int
     */
    protected $end;
    /**/
    
    /**
     * Collection interval.
     *
     * @octdoc  p:gitgraph/$interval
     * @var     string
     */
    protected $interval = 'day';
    /**/

    /**
     * Graph colors RGB values.
     *
     * @octdoc  p:gitgraph/$colors
     * @var     array
     */
    protected $colors = array(
        'commits' => array(  0,   0,   0),
        'inserts' => array(127, 255,  63),
        'deletes' => array(255,  63,   0),
    );
    /**/

    /**
     * Overwrite parent method, because file input is not supported by this plugin, respectively the
     * input file has to specify a git repository.
     *
     * @octdoc  m:gitgraph/loadFile
     * @param   string      $name               Name of file to load.
     * @return  array                           Status information.
     */
    public function loadFile($name)
    /**/
    {
        $status  = true;
        $msg     = '';
        $content = '';
        
        if (!is_dir($name . '/.git')) {
            $status = false;
            $msg    = 'input is not a git repository';
        } else {
            $content = $name;
        }
        
        return array($status, $msg, $content);
    }

    /**
     * Display usage information.
     *
     * @octdoc  m:gitgraph/usage
     * @param   string          $script     Contains name of the script.
     */
    public function usage($script)
    /**/
    {
        printf("options:
    -i  the input has to point to a git repository

    -r  this parameter is required to specify a timerange in the form of
        YYYY-MM-DD..YYYY-MM-DD

example: %s -i /path/to/git-repository -o - -r 2012-04-01..2012-04-30\n",
            $script
        );
    }
    
    /**
     * Check command-line arguments.
     *
     * @octdoc  m:gitgraph/checkArgs
     * @param   string          $script         Contains name of the script.
     * @param   string          $opt            Commandline options.
     * @return  array                           Status information.
     */
    public function checkArgs($script, array $opt)
    /**/
    {
        $status = true;
        $msg    = '';
        $usage  = '';

        if (!array_key_exists('r', $opt)) {
            $status = false;
            $usage  = sprintf("usage: %s -t ... -i ... -o ... -r ... [-c ...] [-s ...]\n", $script);
        } elseif (preg_match('/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/', $opt['r'], $match)) {
            list($y1, $m1, $d1) = explode('-', $match[1]);
            list($y2, $m2, $d2) = explode('-', $match[2]);

            if (!checkdate($m1, $d1, $y1) || !checkdate($m2, $d2, $y2)) {
                $status = false;
                $msg    = 'invalid date in specified timerange';
            } else {
                $time1 = mktime(0, 0, 0, $m1, $d1, $y1);
                $time2 = mktime(0, 0, 0, $m2, $d2, $y2);

                if ($time1 <= $time2) {
                    $this->start = $time1; $this->end = $time2;
                } else {
                    $this->start = $time2; $this->end = $time1;
                }
            }
        } else {
            $status = false;
            $msg    = 'wrong timerange paramater';
        }

        return array($status, $msg, $usage);
    }

    /**
     * Git log parser.
     *
     * @octdoc  m:gitgraph/parse
     * @param   string              $content                Path to git repository.
     */
    public function parse($content)
    /**/
    {   
        // initialization
        switch ($this->interval) {
        case 'day':
            $date_pattern  = '%Y-%m-%d';
            $parse_pattern = '/^date: *(\d{4}-\d{2}-\d{2})/i';
            break;
        case 'month':
            $date_pattern  = '%Y-%m';
            $parse_pattern = '/^date: *(\d{4}-\d{2})/i';
            break;
        default:
            die("invalid interval \"$this->interval\"\n");
        }

        $data = array();
        $time = $this->start;

        do {
            $data[strftime($date_pattern, $time)] = array(#
                'commits' => 0,
                'files'   => 0,
                'inserts' => 0,
                'deletes' => 0
            );

            $time = strtotime('+1 ' . $this->interval, $time);
        } while($time < $this->end);

        $descriptors = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('file', '/dev/stderr', 'a')
        );

        $pipes = array();
        $date  = '';
        $rows  = 0;

        $cmd = sprintf(
            'git log --since=%s --until=%s --date=short --stat',
            escapeshellarg($this->start), 
            escapeshellarg($this->end)
        );

        // execute command and process output
        fwrite(STDERR, "processing log. please wait ...\n");

        if (!($ph = proc_open($cmd, $descriptors, $pipes))) {
            die("unable to execute \"$cmd\"\n");
        }

        fclose($pipes[0]);

        while (!feof($pipes[1])) {
            $row = fgets($pipes[1]);

            if (preg_match($parse_pattern, $row, $match)) {
                $date = $match[1];
                ++$rows;

                if (!isset($data[$date])) {
                    die("log date out of range \"$date\"\n");
                } else {
                    ++$data[$date]['commits'];
                }
            } elseif (preg_match('/(\d+) files changed, (\d+) insertions\(\+\), (\d+) deletions\(-\)/i', $row, $match)) {
                if ($date == '') {
                    die("parse error at \"$row\"\n");
                }

                $data[$date]['files']   += $match[1];
                $data[$date]['inserts'] += $match[2];
                $data[$date]['deletes'] += $match[3];
            }
        }

        fclose($pipes[1]);

        $code = proc_close($ph);

        if ($rows == 0) {
            die("nothing todo\n");
        }

        return $this->graph($data);
    }

    /**
     * Create graph from provided data.
     *
     * @octdoc  m:gitgraph/graph
     * @param   array               $data                   Data to create graph from.
     */
    protected function graph($data)
    /**/
    {
        // get max values and other initialization
        $max = array('commits' => array(), 'changes' => array());

        foreach ($data as $date => $values) {
            $max['commits'][] = $values['commits'];
            $max['changes'][] = $values['inserts'];
            $max['changes'][] = $values['deletes'];
        }

        $max['commits'] = max($max['commits']);
        $max['changes'] = max($max['changes']);

        // create imagemagick MVG commands for drawing graph
        $bar_width = $this->bar_width;
        $inc_width = $bar_width * 8;
        $colors    = $this->colors;

        $width  = count($data) * $inc_width;
        $height = $width * 0.5;

        $context = $this->getContext();
        $context->xs = 1;
        $context->ys = 1;
        $context->setSize($width, $height);

        $m1 = $height / $max['commits'];
        $m2 = $height / $max['changes'];

        $render = function($type, $mul, $offset) use ($context, $colors, $bar_width, $inc_width, $height, $data) {
            $ctx = $context->addContext();
            $x   = $offset;

            $ctx->addCommand(vsprintf('fill rgb(%d,%d,%d)', $colors[$type]));
            $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d)', $colors[$type]));

            foreach ($data as $date => $values) {
                if ($values[$type] > 0) {
                    $ctx->addCommand(sprintf(
                        'rectangle %f,%f %f,%f', 
                        $x, $height, $x + $bar_width, $height - $values[$type] * $mul
                    ));
                }

                $x += $inc_width;
            }
        };

        $render('commits', $m1, $bar_width * 0);
        $render('inserts', $m2, $bar_width * 2);
        $render('deletes', $m2, $bar_width * 4);

        $commands = $this->getCommands();

        return $commands;
    }
}
