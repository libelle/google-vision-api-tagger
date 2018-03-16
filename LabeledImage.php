<?php
/**
 * Class LabeledImage
 * Stupid class for using exiv2 for manipulating EXIF, IPTC, and XMP in images/sidecars.
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
class LabeledImage
{
    /**
     * @var array Mapping of generic header fields to corresponding XMP, IPTC, and EXIF fields
     */
    public static $metadata_map = array(
        'title'=>array('Iptc.Application2.ObjectName','Iptc.Application2.Subject','Xmp.dc.title'),
        'caption'=>array('Iptc.Application2.Caption','Xmp.dc.description'),
        'headline'=>array('Iptc.Application2.Headline','Xmp.photoshop.Headline'),
        'author'=>array('Exif.Image.Artist','Iptc.Application2.Byline','Iptc.Application2.Credit','Xmp.dc.creator','Xmp.photoshop.Credit'),
        'copyright'=>array('Exif.Image.Copyright','Iptc.Application2.Copyright','Xmp.dc.rights'),
        'source'=>array('Iptc.Application2.Source','Xmp.photoshop.Source'),
        'contact_email'=>array('Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiEmailWork'),
        'contact_phone'=>array('Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiTelWork'),
        'keywords'=>array('Iptc.Application2.Keywords','Xmp.dc.subject'),
        'url'=>array('Xmp.xmpRights.WebStatement','Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiUrlWork'),
        'city'=>array('Iptc.Application2.City','Xmp.photoshop.City'),
        'state'=>array('Xmp.photoshop.State','Iptc.Application2.ProvinceState'),
        'country'=>array('Iptc.Application2.CountryName','Xmp.photoshop.Country'),
        'country_code'=>array('Iptc.Application2.CountryCode'),
        'software'=>array('Exif.Image.Software','Xmp.xmp.CreatorTool'),
    );

    /**
     * Use exiv2 to pull metadata from an image file into a text file
     * @param $exiv2_path string path to exiv2 binary
     * @param $filename string image filename
     * @return string metadata text filename
     */
    public static function exportMetadata($exiv2_path,$filename)
    {
        $metadata_file = $filename . '-metadata.txt';
        if (file_exists($filename))
        {
            $cmd = $exiv2_path . ' -Pkt ' . escapeshellarg($filename) . ' > ' . escapeshellarg($metadata_file) . ' 2>&1  < /dev/null ';
            $res = exec($cmd);
        }
        else
            touch($metadata_file);
        return $metadata_file;
    }

    /**
     * Use exiv2 to create an XMP sidecar file from an image
     * @param $exiv2_path string path to exiv2 binary
     * @param $filename string image filename
     */
    public static function createSidecar($exiv2_path, $filename)
    {
        $cmd = $exiv2_path . ' -eX ' . escapeshellarg($filename);
        exec($cmd);
    }

    /**
     * Tag string sanitizer. Does not contain isopropynol. May need to be enhanced if you have use multi-byte strings.
     * @param $str
     * @return string
     */
    public static function sanitize($str)
    {
        return trim(str_replace(array("\n", "\r"), array(' ',''), $str));
    }

    /**
     * Use exiv2 to push metadata from a command file into an image or XMP file
     * @param $exiv2_path string path to exiv2 binary
     * @param $filename string image or XMP filename
     * @param $headers array named headers
     */
    public static function applyMetadataScript($exiv2_path, $filename, $headers)
    {
        $command_file = $filename . '-metadata_commands.txt';
        $handle = fopen($command_file, 'w');
        $headers['software'][] = 'labeled via tagger/Google Vision API';
        foreach ($headers as $key => $tv)
        {
            if ($key == 'keywords')
            {
                fwrite($handle, "del Iptc.Application2.Keywords\n");
                fwrite($handle, "del Xmp.dc.subject\n");

                if (!empty($tv))
                {
                    foreach ($tv as $this_keyword)
                    {
                        $skeyw = self::sanitize($this_keyword);
                        fwrite($handle, "add Iptc.Application2.Keywords $skeyw\n");
                        fwrite($handle, "set Xmp.dc.subject $skeyw\n");
                    }
                }
            }
            else
            {
                $map = (isset(self::$metadata_map[$key]) ? self::$metadata_map[$key] : false);
                if ($map)
                {
                    foreach ($map as $mapped_key)
                    {
                        if (!empty($tv))
                        {
                            fwrite($handle, "del $mapped_key\n");
                            if (is_array($tv))
                            {
                                array_walk($tv, function (&$k) {
                                    $k=self::sanitize($k);
                                });
                                fwrite($handle, "set $mapped_key " . implode(',', $tv) . "\n");
                            }
                            else
                            {
                                fwrite($handle, "set $mapped_key ".self::sanitize($tv)."\n");
                            }
                        }
                    }
                }
            }

        }
        fclose($handle);
        $cmd = $exiv2_path . ' -m ' . escapeshellarg($command_file) . ' ' . escapeshellarg($filename);
        $res = exec($cmd);
    }


    /**
     * Export an image or XMP metadata into a file, and then load them up into an array for use elsewhere
     * @param $exiv2_path string path to exiv2 binary
     * @param $filename string image or XMP filename
     * @param $unique bool disallow repeating values for such things as keywords
     * @return array
     */
    public static function getImageHeaders($exiv2_path, $filename, $unique=true)
    {
        $image_metadata = array();
        $meta_spec = self::exportMetadata($exiv2_path,$filename);
        $meta = file($meta_spec);
        $keys = array_keys(self::$metadata_map);
        foreach($keys as $key)
        {
            $image_metadata[$key]=array();
        }
        foreach ($meta as $tl)
        {
            // key val
            $bits = preg_split('/\s+/',$tl);
            $key = self::metaDataKeyFind($bits[0]);
            if ($key !== false)
            {
                array_shift($bits); // key
                $val = implode(' ',$bits);
                $val = trim(preg_replace('/lang="[^"]+"/','',$val));
                if ($key == 'keywords')
                {
                    $vals = explode(',',$val);
                    foreach($vals as $tv)
                    {
                        $tk = trim($tv);
                        $image_metadata[$key][] = trim($tk);
                    }
                }
                else
                    $image_metadata[$key][] = $val;
            }
        }
        if ($unique)
        {
            foreach ($image_metadata as $key=>$val)
            {
                $image_metadata[$key] = array_unique($val);
            }
        }

        return $image_metadata;
    }


    /**
     * Find a meta data key in the mapping
     * @param $key string the key
     * @return bool|string the shorthand string key for the IPTC, EXIF, or XMP specific key
     */
    public static function metaDataKeyFind($key)
    {
        $ret = false;
        foreach (self::$metadata_map as $tk=>$tl)
        {
            if (array_search($key,$tl) !== false)
            {
                $ret = $tk;
                break;
            }
        }
        return $ret;
    }

    /**
     * Create a scaled smaller version of an image into a JPG using
     * @param $src string filename of image
     * @param $config array containing keys for working directory, resolution, and jpeg quality.
     * @return string small image filespec
     */
    public static function scaledVersion($src,$config)
    {
        $image = imagecreatefromjpeg($src);
        $tmpfname = tempnam($config['tmp_dir'], "sml");
        $tmpjpegname = $tmpfname . '.jpg';
        $source_imagex = imagesx($image);
        $source_imagey = imagesy($image);
        if ($source_imagex > $source_imagey)
            $rat = $config['resolution'] / $source_imagex;
        else
            $rat = $config['resolution'] / $source_imagey;
        $dst_img = imagecreatetruecolor($source_imagex * $rat, $source_imagey * $rat);
        imagecopyresampled($dst_img, $image, 0, 0, 0, 0, $source_imagex * $rat, $source_imagey * $rat, $source_imagex, $source_imagey);
        imagejpeg($dst_img, $tmpjpegname, isset($config['working_quality'])?$config['working_quality']:70);
        imagedestroy($dst_img);
        imagedestroy($image);
        unlink($tmpfname);
        return $tmpjpegname;
    }
}