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
    protected $bar_width = 5;
    /**/
    
    /**
     * Height of image to create.
     *
     * @octdoc  p:gitgraph/$height
     * @var     int
     */
    protected $height = 768;
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
        'commit' => array(  0,   0,   0),
        'insert' => array(255,   0,   0),
        'delete' => array(  0, 255,   0),
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
     * @return  array                           Status information.
     */
    public function checkArgs()
    /**/
    {
        $status = true;
        $msg    = '';

        $opt = getopt('r:');

        if (!array_key_exists('r', $opt)) {
            $status = false;
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

        return array($status, $msg);
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
            die('invalid interval "' . $interval . '"');
        }

        $data = array();
        $time = $thos->start;

        do {
            $data[strftime($date_pattern, $time)] = array(#
                'commits' => 0,
                'files'   => 0,
                'inserts' => 0,
                'deletes' => 0
            );

            $time = strtotime('+1 ' . $interval, $time);
        } while($time <= $this->end);

        $descriptors = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('file', '/dev/stderr', 'a')
        );

        $pipes = array();
        $date  = '';

        $cmd = sprintf(
            'git log --since=%s --until=%s --date=short --stat',
            escapeshellarg($this->start), 
            escapeshellarg($this->end)
        );

        // execute command and process output
        fwrite(STDERR, "processing log. please wait ...\n");

        if (!($ph = proc_open($cmd, $descriptors, $pipes))) {
            $this->usage('unable to execute "' . $cmd . '"');
        }

        fclose($pipes[0]);

        while (!feof($pipes[1])) {
            $row = fgets($pipes[1]);

            if (preg_match($parse_pattern, $row, $match)) {
                $date = $match[1];

                if (!isset($data[$date])) {
                    $this->usage('log date out of range "' . $date . '"');
                } else {
                    ++$data[$date]['commits'];
                }
            } elseif (preg_match('/(\d+) files changed, (\d+) insertions\(\+\), (\d+) deletions\(-\)/i', $row, $match)) {
                if ($date == '') {
                    $this->usage('parse error at "' . $row . '"');
                }

                $data[$date]['files']   += $match[1];
                $data[$date]['inserts'] += $match[2];
                $data[$date]['deletes'] += $match[3];
            }
        }

        fclose($pipes[1]);

        $code = proc_close($ph);

        $this->graph($data);
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

        $m1 = $this->height / $max['commits'];
        $m2 = $this->height / $max['changes'];

        // create imagemagick MVG commands for drawing graph
        $commands = array();
        $commands[] = vsprintf('fill rgb(%d,%d,%d)', $this->colors['commits']);

        foreach ($data as $date => $values) {
            $pos = 0;

            $commands[] = sprintf('translate 0,%d', $pos);
            $commands[] = sprintf('rectangle 0,0 %f,%f', $values['commits'] * $m1, 5);

            $pos += 10;
        }
    }
}
