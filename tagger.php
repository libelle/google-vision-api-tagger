<?php
/**
 * Google Vision API image tagger with color-to-name and face-anonymization functionality
 * New, and spectacularly crufty!
 * SjG <github@fogbound.net>
 * https://github.com/libelle/google-vision-api-tagger

MIT License

Copyright (c) 2018 Samuel Goldstein

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */
require 'config.php';
require 'LabeledImage.php';
require 'ColorNamer.php';
require 'GoogleCloudVision.php';

use GoogleCloudVisionPHP\GoogleCloudVision;

$options = getopt('s:i::c::vafnhw');
if (!$options || isset($options['h']) || !isset($options['s']) || empty($options['s']))
{
    echo "Usage: tagger.php -s [source directory]\n";
    echo "options:  -i [field list] -c [mode] -v -a -f -n -h\n";
    echo " where:\n";
    echo "   [field list] is one or more of:\n";
    echo "      k (keywords)\n";
    echo "      o (OCR text)\n";
    echo "      l (logos)\n";
    echo "      m (landmarks)\n";
    echo "      c (color information)\n";
    echo "      field list defaults to all fields being active\n";
    echo "   -h is help (you're reading it!)\n";
    echo "   -v is verbose\n";
    echo "   -w is write summary file\n";
    echo "   -a is anonymize faces before uploading\n";
    echo "   -f is force reprocessing\n";
    echo "   -n is no cleanup\n";
    echo "   -c [mode] is which color label mapping type is used by the color option:\n";
    echo "       c - use the set of CSS named colors\n";
    echo "       p - use only the primary/secondary/tertiary RGB color wheel (default)\n";
    echo "\n";
    echo "example:\n";
    echo "tagger.php -s images -i ko\n";
    echo "will process images from images/ directory, and ignore fields other than OCR and Keywords\n";
    die;
}

$verbose = isset($options['v']);
$anonymize = isset($options['a']);
$force = isset($options['f']);
$noclean = isset($options['n']);
$fields = isset($options['i'])?$options['i']:'kolcm';
$cmode = isset($options['c'])?$options['c']:'p';
$summary = isset($options['w']);

if ($verbose) echo "Running in verbose mode\n";
if ($verbose) echo "Processing images from {$options['s']}\n";
if ($verbose) echo "Will " . ($anonymize ? '' : 'not ') . "anonymize faces\n";
if ($verbose) echo "Will " . ($force ? '' : 'not ') . "force re-tagging\n";
if ($verbose) echo "Will " . ($noclean ? 'not ' : '') . "clean up temporary files\n";


$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($options['s']), RecursiveIteratorIterator::SELF_FIRST);

$gcv = new GoogleCloudVision();
$gcv->setKey($config['apikey']);

foreach ($objects as $name => $object)
{
    if (preg_match('/\.jpg$|\.jpeg$|\.nef$/i', $name))
    {
        if ($verbose) echo "-> $name\n";
        $source_file = $object->getRealPath();
        if (file_exists(preg_replace('/\.jpg$|\.jpeg$|\.nef$/i', '.XMP', $source_file)))
            $source_file_xmp = preg_replace('/\.jpg$|\.jpeg$|\.nef$/i', '.XMP', $source_file);
        else
            $source_file_xmp = preg_replace('/\.jpg$|\.jpeg$|\.nef$/i', '.xmp', $source_file);
        if (!file_exists($source_file_xmp))
            LabeledImage::createSidecar($config['exiv2_path'], $source_file); // create if doesn't exist
        $jpg_headers = LabeledImage::getImageHeaders($config['exiv2_path'], $source_file);
        $xmp_headers = LabeledImage::getImageHeaders($config['exiv2_path'], $source_file_xmp);
        $headers = array_merge($jpg_headers, $xmp_headers);

        // check if already processed
        $tagged = array_reduce($headers['software'], function ($prev, $curr) {
            return ($prev || stripos($curr, 'tagger') !== false);
        }, false);
        if (!$tagged || $force)
        {
            $delete_jpg = false;
            if (preg_match('/\.nef$/i', $name))
            {
                if ($verbose) echo "--> creating jpg working copy\n";
                $jpeg_file = preg_replace('/\.nef$/i', '.jpg', $source_file);
                $cmd = $config['dcraw_path'] . " -c " . escapeshellarg($source_file) . " | " . $config['cjpeg_path'] .
                    " -quality 70 -optimize -progressive -outfile " . escapeshellarg($jpeg_file);
                exec($cmd);
                $delete_jpg = true;
            } else
                $jpeg_file = $source_file;


            // scale version
            if ($verbose) echo "--> creating smaller working copy\n";

            $work_image = LabeledImage::scaledVersion($jpeg_file, $config);

            if ($noclean)
                copy($work_image,str_replace('.jpg','-orig.jpg',$work_image));
            $faces = array();
            if ($anonymize)
            {
                if ($verbose) echo "--> seeking faces\n";
                $faces = array();
                $cmd = '/opt/local/bin/python detect_faces.py -c 0.35 --image ' . escapeshellarg($work_image)
                    . ' --prototxt deploy.prototxt.txt --model res10_300x300_ssd_iter_140000.caffemodel';
                exec($cmd, $faces);
            }

            if ($anonymize)
            {
                $image = imagecreatefromjpeg($work_image);
                $source_imagex = imagesx($image);
                $source_imagey = imagesy($image);

                foreach ($faces as $face)
                {
                    list($x1, $y1, $x2, $y2) = explode(',', $face);
                    if ($verbose) echo "---> Obscuring face found at {$x1}, y: {$y1}\n";
                    $w = ($x2 - $x1);
                    $h = ($y2 - $y1);
                    // icky code to pixelate an ellipse within the detected face
                    $copy = imagecreatefromjpeg($work_image);
                    imagefilter($copy, IMG_FILTER_PIXELATE, 20);
                    $mask = imagecreatetruecolor($source_imagex, $source_imagey);
                    $transparent = imagecolorallocate($mask, 255, 0, 0);
                    imagecolortransparent($mask, $transparent);
                    imagefilledellipse($mask, $x1 + $w / 2, $y1 + $h / 2, $w * 0.6, $h * 0.6, $transparent);
                    $red = imagecolorallocate($mask, 0, 0, 0);
                    imagecopymerge($copy, $mask, 0, 0, 0, 0, $source_imagex, $source_imagey, 100);
                    imagecolortransparent($copy, $red);
                    imagefill($copy, 0, 0, $red);
                    imagecopymerge($image, $copy, 0, 0, 0, 0, $source_imagex, $source_imagey, 100);
                    imagedestroy($mask);
                    imagedestroy($copy);
                }

                imagejpeg($image, $work_image, isset($config['working_quality']) ? $config['working_quality'] : 70);
                imagedestroy($image);
            }

            if ($verbose) echo "--> Getting tags from Google Vision\n";
            $gcv->setImage($work_image);
            $gcv->addFeatureUnspecified(1);
            $gcv->addFeatureLandmarkDetection(2);
            $gcv->addFeatureLogoDetection(2);
            $gcv->addFeatureLabelDetection(5);
            $gcv->addFeatureOCR(1);
            //$gcv->addFeatureSafeSeachDetection(0);
            $gcv->addFeatureImageProperty(1);
            $gcv->setImageContext(array("languageHints" => array($config['language'])));
            $response = $gcv->request();
            if ($noclean)
            {
                file_put_contents($work_image . '.response-json.txt', print_r($response, true));
            }
            $tags = array();
            if (strpos($fields,'k')!==false && isset($response['responses']) && isset($response['responses'][0])
                && isset($response['responses'][0]['labelAnnotations']))
            {
                if ($verbose) echo "---> Processing keywords\n";
                foreach ($response['responses'][0]['labelAnnotations'] as $ta)
                    $tags[] = $ta['description'];
            }
            if (strpos($fields,'c')!==false && isset($response['responses']) && isset($response['responses'][0])
                && isset($response['responses'][0]['imagePropertiesAnnotation'])
                && isset($response['responses'][0]['imagePropertiesAnnotation']['dominantColors'])
                && isset($response['responses'][0]['imagePropertiesAnnotation']['dominantColors']['colors'])
            )
            {
                if ($verbose) echo "---> Processing dominant colors\n";
                $colors = array();
                foreach ($response['responses'][0]['imagePropertiesAnnotation']['dominantColors']['colors'] as $dc)
                {

                    if ($dc['score'] > 0.1)
                    {
                        $c = ColorNamer::closest(array($dc['color']['red'], $dc['color']['green'], $dc['color']['blue']));
                        if ($cmode == 'c')
                            $colors[$c['css']] = 1;
                        else
                            $colors[$c['wheel']] = 1;
                    }
                }
                $tags = array_merge($tags, array_keys($colors));
            }

            if (strpos($fields,'l')!==false  && isset($response['responses']) && isset($response['responses'][0])
                && isset($response['responses'][0]['logoAnnotations']))
            {
                if ($verbose) echo "---> Processing logos\n";
                foreach ($response['responses'][0]['logoAnnotations'] as $ta)
                    $tags[] = $ta['description'];
            }

            if (strpos($fields,'m')!==false  && isset($response['responses']) && isset($response['responses'][0])
                && isset($response['responses'][0]['landmarkAnnotations']))
            {
                if ($verbose) echo "---> Processing landmarks\n";
                foreach ($response['responses'][0]['landmarkAnnotations'] as $ta)
                    $tags[] = $ta['description'];
            }



            if (strpos($fields,'o')!==false && isset($response['responses']) && isset($response['responses'][0])
                && isset($response['responses'][0]['textAnnotations'])
                && isset($response['responses'][0]['textAnnotations'][0])
            )
            {
                if ($verbose) echo "---> Processing OCR text \n";
                $headers['caption'] = $response['responses'][0]['textAnnotations'][0]['description'];
            }

            if ($summary)
                file_put_contents(str_replace('.jpg','-summary.txt',$work_image),
                    print_r(array_merge(array($headers['caption']),$tags),true));

            $headers['keywords'] = array_merge($headers['keywords'], $tags);
            if ($verbose) echo "--> Writing $source_file\n";
            LabeledImage::applyMetadataScript($config['exiv2_path'], $source_file, $headers);
            if ($verbose) echo "--> Writing $source_file_xmp\n";
            LabeledImage::applyMetadataScript($config['exiv2_path'], $source_file_xmp, $headers);

            if (!$noclean)
            {
                if ($verbose) echo "-> Cleaning up.\n";
                unlink($work_image);
                if ($delete_jpg)
                    unlink($jpeg_file);
                unlink("$source_file-metadata.txt");
                unlink("$source_file-metadata_commands.txt");
            }
            if ($verbose) echo "-> done.\n";

        }
        else if ($verbose) echo "-> Already been processed.\n";
    }
}
