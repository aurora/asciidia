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

namespace asciidia\backend {
    /**
     * Backend class for SVG output format.
     *
     * @octdoc      c:backend/svg
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class svg extends \asciidia\backend
    /**/
    {
        /**
         * DOMDocument.
         *
         * @octdoc  p:svg/$doc
         * @type    \DOMDocument|null
         */
        protected $doc = null;
        /**/
        
        /**
         * Return diagram document.
         *
         * @octdoc  m:svg/getDocument
         * @return  string                      Document.
         */
        public function getDocument()
        /**/
        {
            list($w, $h) = $this->getSize();

            $svg = $this->doc->documentElement;
            
            $svg->setAttribute('viewbox', sprintf('0 0 %d %d', $w, $h));
            $svg->setAttribute('width', $w);
            $svg->setAttribute('height', $h);
            
            // { apply crisp-edges "trick" if necessary
            list($xs, $ys) = $this->context->getCellSize();

            $tx = ($xs % 2 == 0 ? 0.5 : 0);
            $ty = ($ys % 2 == 0 ? 0.5 : 0);
            
            if ($tx != 0 || $ty != 0) {
                $svg->firstChild->setAttribute('transform', sprintf(
                    'translate(%f %f)',
                    $tx, $ty
                ));
            }
            // }
            
            return $this->doc->saveXML();
        }

        /**
         * Return instance of main context.
         *
         * @octdoc  m:svg/getContext
         * @return  context                     Instance of main context.
         */
        public function getContext()
        /**/
        {
            if (is_null($this->context)) {
                $this->doc = new \DOMDocument();
                
                $svg = $this->doc->appendChild($this->doc->createElement('svg'));
                $svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                $svg->setAttribute('version', '1.1');
                
                $this->context = new \asciidia\backend\svg\context($svg);
            }
        
            return $this->context;
        }

        /**
         * No additional tools required for this backend.
         *
         * @octdoc  m:svg/testEnv
         * @return  array                           Status information.
         */
        public function testEnv()
        /**/
        {
            $status = true;
            $msg    = '';
            $out    = array();
            $err    = 0;
        
            return array($status, $msg);
        }

        /**
         * Save a file or test if file can be saved to.
         *
         * @octdoc  m:svg/saveFile
         * @param   string      $name               Name of file to save.
         * @param   string      $document           Document to save.
         * @param   string      $fmt                Output file format.
         */
        public function saveFile($name, $document, $fmt)
        /**/
        {
            file_put_contents(
                ($name == '-' ? 'php://stdout' : $name),
                $document
            );
        }
    }
}
