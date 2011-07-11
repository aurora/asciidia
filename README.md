asciidia
========

Usage
-----

    $ ./asciidia.php [-t ...] [-r] -i ... -o ...

Description
-----------

Asciidia generates bitmap files (png) from simple ASCII diagrams. In that it is similar
to programs like [ditaa](http://ditaa.sourceforge.net/). However: motivation for writing
asciidia was not to replace ditaa or similar tools. Instead i was not satisfied with some 
diagrams produced with ditaa especially i was not able to render nice looking directory 
trees with it.

Because of this, asciidia is more a quick hack with very limited functionality compared 
to a full-featured application like ditaa. For large diagrams asciidia might have 
performance issues. It's not recommended to render very large directory trees with it.

Asciidia requires and uses imagemagick to render it's diagrams to a bitmap.

### Parameters

    -t  Optional type: "tree" or "diagram". Default is: "diagram".
    -r  Optional flag to output the imagemagick draw commands instead of creating a bitmap.
    -i  Required input filename. If "-" is specified, input is read from STDIN. If a 
        directory is specified, the directory will be drawn as tree-diagram instead.
    -o  Required output filename. If "-" is specified, output is written to STDOUT.

Requirements
------------

*   php 5.3
*   imagemagick

License
-------

asciidia

Copyright (C) 2011 by Harald Lapp <<harald@octris.org>>
 
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
