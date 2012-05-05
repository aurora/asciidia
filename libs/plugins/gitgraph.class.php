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
// require_once(__DIR__ . '/../util/exec.class.php');

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
     * Minimum X-Size of grid
     *
     * @octdoc  p:gitgraph/$grid_x
     * @var     int
     */
    protected $grid_min_x = 20;
    /**/

    /**
     * Minimum X-Size of graph
     *
     * @octdoc  p:gitgraph/$grid_x
     * @var     int
     */
    protected $min_x = 1000;
    /**/

    /**
     * Work directory to set for git command.
     *
     * @octdoc  p:gitgraph/$cwd
     * @var     string
     */
    protected $cwd;
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
     * Allowed units to specify for segmentation (interval).
     *
     * @octdoc  p:gitgraph/$units
     * @var     array
     */
    protected $units = array('day', 'month', 'week');
    /**/

    /**
     * Supported graphs.
     *
     * @octdoc  p:gitgraph/$graphs
     * @var     array
     */
    protected $graphs = array('commits', 'commits_avg', 'files', 'files_avg', 'changes', 'changes_avg');
    /**/

    /**
     * Graphs to render.
     *
     * @octdoc  p:gitgraph/$render_graphs
     * @var     array
     */
    protected $render_graphs = array('commits');
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
        'commits'     => array(191, 191, 191),
        'commits_avg' => array(  0,   0,   0),
        'files'       => array(  0,   0, 127),
        'files_avg'   => array(  0,   0,   0),
        'sloc'        => array(  0,   0, 127),
        'sloc_avg'    => array(  0,   0,   0),
        'inserts'     => array(127, 255,  63),
        'deletes'     => array(255,  63,   0),
    );
    /**/

    /**
     * Overwrite parent method, because file input is not supported by this plugin, respectively the
     * input file has to specify a git repository and is checked by the checkArgs method.
     *
     * @octdoc  m:gitgraph/loadFile
     * @param   string      $name               Name of file to load.
     * @return  array                           Status information.
     */
    public function loadFile($name)
    /**/
    {        
        return array(true, '', '');
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
    -g  optional comma-separated list of graphs to include in the output. default 

    -i  the input has to point to a git repository

    -r  this parameter is required to specify a timerange in the form of
        YYYY-MM-DD..YYYY-MM-DD

    -u  optional to specify the unit for segmentating the timerange. default is
        'day', allowed values are:

        %1$s

example: %2$s -i /path/to/git-repository -o - -r 2012-04-01..2012-05-01
example: %2$s -i /path/to/git-repository -o - -r 2011-01-01..2012-01-01 -u week
example: %2$s -i /path/to/git-repository -o - -r 2011-01-01..2012-01-01 -u week -g commits,sloc\n",
            implode("\n        ", $this->units),
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

        $this->cwd = realpath($opt['i']);

        if (!is_dir($this->cwd . '/.git')) {
            $status = false;
            $msg    = 'input is not a path to a git repository';
        } elseif (!array_key_exists('r', $opt)) {
            $status = false;
            $usage  = sprintf("usage: %s -t ... -i ... -o ... -r ... [-g ...] [-u ...] [-c ...] [-s ...]\n", $script);
        } else {
            if (preg_match('/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/', $opt['r'], $match)) {
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
            if (array_key_exists('u', $opt)) {
                if (!in_array($opt['u'], $this->units)) {
                    $status = false;
                    $msg    = 'invalid unit specified';
                } else {
                    $this->interval = $opt['u'];
                }
            }
            if (array_key_exists('g', $opt)) {
                $graphs = explode(',', $opt['g']);

                if (count($tmp = array_diff($graphs, $this->graphs)) > 0) {
                    $status = false;
                    $msg    = 'invalid graph name(s) "' . implode(',', $tmp) . '"';
                } else {
                    $this->render_graphs = $graphs;
                }
            }
        }

        return array($status, $msg, $usage);
    }

    /**
     * Git log parser for commits.
     *
     * @octdoc  m:gitgraph/collectCommits
     * @return  array                                   Collected data.
     */
    public function collectCommits()
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
        case 'week':
            $date_pattern  = '%Y-%W';
            $parse_pattern = '/^date: *(\d{4}-\d{2}-\d{2})/i';
            break;
        default:
            die("invalid interval \"$this->interval\"\n");
        }

        $data = array();
        $time = $this->start;

        do {
            $data[strftime($date_pattern, $time)] = array(
                'commits' => 0,
                'files'   => 0,
                'inserts' => 0,
                'deletes' => 0,
                'sloc'    => 0
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

        if (!($ph = proc_open($cmd, $descriptors, $pipes, $this->cwd))) {
            die("unable to execute \"$cmd\"\n");
        }

        fclose($pipes[0]);

        while (!feof($pipes[1])) {
            $row = fgets($pipes[1]);

            if (preg_match($parse_pattern, $row, $match)) {
                $date = strftime($date_pattern, strtotime($match[1]));
                ++$rows;

                if (!isset($data[$date])) {
                    die("log date out of range \"$date\"\n");
                } else {
                    ++$data[$date]['commits'];
                }
            }
        }

        fclose($pipes[1]);

        $code = proc_close($ph);

        if ($rows == 0) {
            die("nothing todo\n");
        }

        return $data;
    }

    /**
     * Git log parser for commits, files, sloc.
     *
     * @octdoc  m:gitgraph/collectFull
     * @return  array                                   Collected data.
     */
    public function collectFull()
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
        case 'week':
            $date_pattern  = '%Y-%W';
            $parse_pattern = '/^date: *(\d{4}-\d{2}-\d{2})/i';
            break;
        default:
            die("invalid interval \"$this->interval\"\n");
        }

        $data  = array();
        $time  = $this->start;
        $first = null;

        do {
            $key = strftime($date_pattern, $time);
            $data[$key] = array(
                'commits' => 0,
                'files'   => 0,
                'inserts' => 0,
                'deletes' => 0,
                'sloc'    => 0
            );

            if (is_null($first)) $first =& $data[$key];

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
        $prev  = '';

        $cmd = 'git log --reverse -p --date=short'

        // execute command and process output
        fwrite(STDERR, "processing log. please wait ...\n");

        if (!($ph = proc_open($cmd, $descriptors, $pipes, $this->cwd))) {
            die("unable to execute \"$cmd\"\n");
        }

        fclose($pipes[0]);

        $tmp = array(
            'files' => 0,
            'sloc'  => 0
        );

        while (!feof($pipes[1])) {
            $row = fgets($pipes[1]);

            if (preg_match($parse_pattern, $row, $match)) {
                $timestamp = strtotime($match[1]);
                $date      = strftime($date_pattern, $timestamp);

                if ($timestamp >= $this->end) break;

                ++$rows;

                if (isset($data[$date])) {
                    ++$data[$date]['commits'];
                }
            } elseif ($date != '') {
                if (substr($row, 0, 4) == '+++ ') {
                    if (substr($prev, 0, 13) == '--- /dev/null') {
                        ++$tmp['files'];
                    } elseif (substr($row, 0, 13) == '+++ /dev/null') {
                        --$tmp['files'];
                    }

                    if (isset($data[$date])) {
                        $data[$date]['files'] = $tmp['files'];
                    } elseif ($timestamp < $this->start) {
                        $first['files'] = $tmp['files'];
                    }
                } elseif (substr($row, 0, 4) == '--- ') {
                } elseif (substr($row, 0, 1) == '-') {
                    --$tmp['sloc'];

                    if (isset($data[$date])) {
                        ++$data[$date]['deletes'];
                        $data[$date]['sloc'] = $tmp['sloc'];
                    } elseif ($timestamp < $this->start) {
                        $first['sloc'] = $tmp['sloc'];
                    }
                } elseif (substr($row, 0, 1) == '+') {
                    ++$tmp['sloc'];

                    if (isset($data[$date])) {
                        ++$data[$date]['inserts'];
                        $data[$date]['sloc'] = $tmp['sloc'];
                    } elseif ($timestamp < $this->start) {
                        $first['sloc'] = $tmp['sloc'];
                    }
                }
            }

            $prev = $row;
        }

        fclose($pipes[1]);

        $code = proc_close($ph);

        if ($rows == 0) {
            die("nothing todo\n");
        }

        reset($data);
        $prev = current($data);

        while (next($data)) {
            $key = key($data);

            if ($data[$key]['commits'] == 0) {
                $data[$key]['files'] = $prev['files'];
                $data[$key]['sloc']  = $prev['sloc'];
            }

            $prev = $data[$key];
        }

        return $data;
    }

    /**
     * Create graph from provided data.
     *
     * @octdoc  m:gitgraph/graph
     * @param   array               $data                   Data to create graph from.
     * @param   array               $types                  Array of graph types and boolen value whether to render or not to render graph.
     */
    protected function graph($data, array $types)
    /**/
    {
        // get max values and other initialization
        $max = array('commits' => array(), 'files' => array(), 'sloc' => array());

        foreach ($data as $date => $values) {
            $max['commits'][] = $values['commits'];
            $max['files'][]   = $values['files'];
            $max['sloc'][]    = $values['sloc'] + $values['inserts'];
        }

        $max['commits'] = max($max['commits']);
        $max['files']   = max($max['files']);
        $max['sloc']    = max($max['sloc']);

        // create imagemagick MVG commands for drawing graph
        $width     = $this->grid_min_x * count($data);
        $inc_width = $this->grid_min_x;

        if ($width < $this->min_x) {
            $width     = $this->min_x;
            $inc_width = $width / count($data);
        }

        $height = $width * 0.5;

        $bar_width = max(5, $inc_width - 5);

        $context = $this->getContext();
        $context->xs = 1;
        $context->ys = 1;
        $context->setSize($width, $height + 50);

        if ($types['commits'] || $types['commits_avg']) {
            // render commits
            $mul = $height / $max['commits'];
            $avg = array();

            $ctx = $context->addContext();
            $x   = 0;
            $i   = 0;

            if ($types['commits']) {
                $ctx->addCommand(vsprintf('fill rgb(%d,%d,%d)', $this->colors['commits']));
                $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d)', $this->colors['commits']));
            }

            foreach ($data as $date => $values) {
                if ($values['commits'] > 0 && $types['commits']) {
                    $ctx->addCommand(sprintf(
                        'rectangle %f,%f %f,%f', 
                        $x, $height, $x + $bar_width, $height - $values['commits'] * $mul
                    ));
                }

                $avg[] = ($i == 0 ? 0 : ($avg[$i - 1] + $values['commits']) / 2);

                $x += $inc_width;
                ++$i;
            }

            if ($types['commits_avg']) {
                $points = array();
                for ($i = 0, $cnt = count($avg); $i < $cnt; ++$i) {
                    $xoffs = ($i == 0
                                ? 0
                                : ($i == $cnt - 1
                                    ? $bar_width
                                    : $bar_width / 2));

                    $points[] = array(
                        $i * $inc_width + $xoffs, $height - $avg[$i] * $mul
                    );
                }

                $ctx = $context->addContext();
                $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d) stroke-width 3', $this->colors['commits_avg']));
                $ctx->drawSpline($points);
            }
        }

        if ($types['files'] || $types['files_avg']) {
            // render files
            $mul = $height / $max['files'];
            $idx = 0;
            $inc = $bar_width / 2;
            $avg = array();

            $ctx = $context->addContext();
            $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d) stroke-width 3', $this->colors['files']));

            $points = array();
            foreach ($data as $date => $values) {
                $points[] = array(
                    $idx * $inc_width + $inc, $height - $values['files'] * $mul
                );

                $avg[] = ($idx == 0 ? 0 : ($avg[$idx - 1] + $values['files']) / 2);

                ++$idx;
            }

            if ($types['files']) {
                for ($i = 1, $cnt = count($points); $i < $cnt; ++$i) {
                    list($x1, $y1) = $points[$i - 1];
                    list($x2, $y2) = $points[$i];

                    $ctx->drawLine($x1, $y1, $x2, $y2);
                }
            }

            if ($types['files_avg']) {
                $points = array();
                for ($i = 0, $cnt = count($avg); $i < $cnt; ++$i) {
                    $xoffs = ($i == 0
                                ? 0
                                : ($i == $cnt - 1
                                    ? $bar_width
                                    : $bar_width / 2));

                    $points[] = array(
                        $i * $inc_width + $xoffs, $height - $avg[$i] * $mul
                    );
                }

                $ctx = $context->addContext();
                $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d) stroke-width 3', $this->colors['files_avg']));
                $ctx->drawSpline($points);
            }
        }

        //     // render sloc
        //     $mul = $height / $max['sloc'];
        //     $idx = 0;

        //     $ctx = $context->addContext();
        //     $ctx->addCommand('stroke-width 3');

        //     $points = array();
        //     foreach ($data as $date => $values) {
        //         $points[] = array(
        //             's' => array(
        //                 $idx * $inc_width + $inc, $height - $values['sloc'] * $mul
        //             ),
        //             'i' => array(
        //                 $idx * $inc_width + $inc, $height - $values['sloc'] * $mul - $values['inserts'] * $mul,
        //                 $idx * $inc_width + $inc, $height - $values['sloc'] * $mul
        //             ),
        //             'd' => array(
        //                 $idx * $inc_width + $inc, $height - $values['sloc'] * $mul + $values['deletes'] * $mul,
        //                 $idx * $inc_width + $inc, $height - $values['sloc'] * $mul
        //             )
        //         );

        //         ++$idx;
        //     }

        //     for ($i = 0, $cnt = count($points); $i < $cnt; ++$i) {
        //         if ($i > 0) {
        //             list($x1, $y1) = $points[$i - 1]['s'];
        //             list($x2, $y2) = $points[$i]['s'];

        //             $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d)', $this->colors['files']));
        //             $ctx->drawLine($x1, $y1, $x2, $y2);
        //         }

        //         list($x1, $y1, $x2, $y2) = $points[$i]['i'];

        //         $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d)', $this->colors['inserts']));
        //         $ctx->drawLine($x1, $y1, $x2, $y2);

        //         list($x1, $y1, $x2, $y2) = $points[$i]['d'];

        //         $ctx->addCommand(vsprintf('stroke rgb(%d,%d,%d)', $this->colors['deletes']));
        //         $ctx->drawLine($x1, $y1, $x2, $y2);
        //     }
        // }

        return $this->getCommands();
    }

    /**
     * Executes gitlog parser.
     *
     * @octdoc  m:gitgraph/parse
     */
    public function parse($content)
    /**/
    {
        if (count(array_intersect($this->render_graphs, array('files', 'files_avg', 'changes', 'changes_avg'))) > 0) {
            $data = $this->collectFull();
        } else {
            $data = $this->collectCommits();
        }

        $types = array();
        foreach ($this->graphs as $type) {
            $types[$type] = in_array($type, $this->render_graphs);
        }

        return $this->graph($data, $types);
    }
}
