# asciidia

## Version
    
    v0.3.4, 2014-03-27

## Usage

    $ ./asciidia.php -h
    $ ./asciidia.php -t ... -h
    $ ./asciidia.php -t ... -i ... -o ... [-c ...] [-s ...]

## Description

Asciidia generates bitmap or vector files from simple ASCII diagrams. In that it is similar
to programs like [ditaa](http://ditaa.sourceforge.net/). It's build on top of a plugin
architecture to make it easy to enhance it with additional diagram or drawing plugins. 
Currently available plugin types are:

- simple ASCII diagrams, currently not as many features as what ditaa provides, though
- directory tree diagrams
- syntax-diagrams (railroad diagrams) by specifying an EBNF
- identicon generator

Asciidia can directly generate SVG and ImageMagick MVG files, it can make use of ImageMagick
"convert" utility to render to many other image formats, too.

### Parameters

    -h  show information about command-line arguments. provide a diagram type
        with '-t' to show help about the plugin

    -t  plugin type to load. available plugins are:

        diagram
        ebnf
        identicon
        test
        tree

    -i  input filename or '-' for STDIN

    -o  output filename or '-' for STDOUT

    -c  defines the widht/height of each cell / character on the canvas in 
        pixel. Notation is ...x... (width x height) or ... (width x width).

    -s  scales image. notation is ...x... (width x height) whereas ... is a 
        number to scale to. if width or height are ommited, image will be 
        scaled by keeping aspect ratio. 

It's possible to specify an output format by prefixing the output filename in the form

    format:filename
    
For example:

    ./asciidia.php -t ebnf -i rules.ebnf -o svg:diagram.svg

## Requirements

*   php 5.3
*   imagemagick is only required, if output format is not 'svg'


## Example

The following embedded image was created on Mac OS X using asciidia and
inkscape with the command:

    /Applications/Inkscape.app/Contents/Resources/bin/inkscape \
        -z -e test/ebnf.png -w 500 \
        <(asciidia -t ebnf -i test/ebnf.txt -o svg:-)

![ebnf](https://github.com/aurora/asciidia/raw/master/test/ebnf.png)

## License

asciidia

Copyright (c) by Harald Lapp <<harald@octris.org>>
 
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
 
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with this program.  If not, see <<http://www.gnu.org/licenses/>>.

## Thanks

I would like to thank the following person(s) for improvments and bug fixing:

*   Liviu Cristian Mirea-Ghiban, http://github.com/liviumirea

    identicon plugin: 

    1.  The sprites were being drawn with an additional 1px per width and height and had bleeding edges in the final image.
    2.  The rotations didn't completely match the ones in the original PHP Identicon script.
