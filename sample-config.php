<?php

$config = array(
    'apikey'=>'google-api-key', // get yours at https://cloud.google.com/vision/
    'language'=>'en', // default language hint for Google
    'resolution'=>400, // size to downsample before processing / sending
    'color_threshold'=>0.1, // color dominance threshold for including as a keyword
    'tmp_dir'=>'/tmp', // directory in which to do the work

    'exiv2_path'=>'/usr/local/bin/exiv2', // path to exiv2 binary for doing all the exif/XMP magic
    'dcraw_path'=>'/usr/local/bin/dcraw', // path to dcraw binary for handling NEFs
    'cjpeg_path'=>'/usr/local/bin/cjpeg', // path to cjpeg binary for converting TIFF to JPG
);
