<?php

/**
 * Class ColorNamer
 * Stupid class for finding the closest color name in RGB space to a specified color.
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
class ColorNamer
{
    /**
     * @var array approximate primary, secondary, tertiary RGB color wheel represented in hue angle
     */
    public static $colorWheelHue = array(
        'Red'=>0,
        'Rose'=>335,
        'Magenta'=>304,
        'Violet'=>274,
        'Blue'=>243,
        'Azure'=>213,
        'Cyan'=>183,
        'SpringGreen'=>152,
        'Green'=>122,
        'Chartreuse'=>91,
        'Yellow'=>61,
        'Orange'=>30,
    );

    /**
     * @var array five gray-scales as a brightness value
     */
    public static $grays = array(
        'Black'=>0,
        'DarkGray'=>0.25,
        'Gray'=>0.5,
        'LightGray'=>0.75,
        'White'=>1
    );

    /**
     * @var array CSS Named colors. Note they don't necessarily match the color wheel values above!
     */
    public static $cssNamedColors = array(
        'AliceBlue' => array(240, 248, 255),
        'Amethyst' => array(153, 102, 204),
        'AntiqueWhite' => array(250, 235, 215),
        'Aqua' => array(0, 255, 255),
        'Aquamarine' => array(127, 255, 212),
        'Azure' => array(240, 255, 255),
        'Beige' => array(245, 245, 220),
        'Bisque' => array(255, 228, 196),
        'Black' => array(0, 0, 0),
        'BlanchedAlmond' => array(255, 235, 205),
        'Blue' => array(0, 0, 255),
        'BlueViolet' => array(138, 43, 226),
        'Brown' => array(165, 42, 42),
        'BurlyWood' => array(222, 184, 135),
        'CadetBlue' => array(95, 158, 160),
        'Chartreuse' => array(127, 255, 0),
        'Chocolate' => array(210, 105, 30),
        'Coral' => array(255, 127, 80),
        'CornflowerBlue' => array(100, 149, 237),
        'Cornsilk' => array(255, 248, 220),
        'Crimson' => array(220, 20, 60),
        'Cyan' => array(0, 255, 255),
        'DarkBlue' => array(0, 0, 139),
        'DarkCyan' => array(0, 139, 139),
        'DarkGoldenrod' => array(184, 134, 11),
        'DarkGray' => array(169, 169, 169),
        'DarkGreen' => array(0, 100, 0),
        'DarkKhaki' => array(189, 183, 107),
        'DarkMagenta' => array(139, 0, 139),
        'DarkOliveGreen' => array(85, 107, 47),
        'DarkOrange' => array(255, 140, 0),
        'DarkOrchid' => array(153, 50, 204),
        'DarkRed' => array(139, 0, 0),
        'DarkSalmon' => array(233, 150, 122),
        'DarkSeaGreen' => array(143, 188, 143),
        'DarkSlateBlue' => array(72, 61, 139),
        'DarkSlateGray' => array(47, 79, 79),
        'DarkTurquoise' => array(0, 206, 209),
        'DarkViolet' => array(148, 0, 211),
        'DeepPink' => array(255, 20, 147),
        'DeepSkyBlue' => array(0, 191, 255),
        'DimGray' => array(105, 105, 105),
        'DodgerBlue' => array(30, 144, 255),
        'FireBrick' => array(178, 34, 34),
        'FloralWhite' => array(255, 250, 240),
        'ForestGreen' => array(34, 139, 34),
        'Fuchsia' => array(255, 0, 255),
        'Gainsboro' => array(220, 220, 220),
        'GhostWhite' => array(248, 248, 255),
        'Gold' => array(255, 215, 0),
        'Goldenrod' => array(218, 165, 32),
        'Gray' => array(128, 128, 128),
        'Green' => array(0, 128, 0),
        'GreenYellow' => array(173, 255, 47),
        'Honeydew' => array(240, 255, 240),
        'HotPink' => array(255, 105, 180),
        'IndianRed' => array(205, 92, 92),
        'Indigo' => array(75, 0, 130),
        'Ivory' => array(255, 255, 240),
        'Khaki' => array(240, 230, 140),
        'Lavender' => array(230, 230, 250),
        'LavenderBlush' => array(255, 240, 245),
        'LawnGreen' => array(124, 252, 0),
        'LemonChiffon' => array(255, 250, 205),
        'LightBlue' => array(173, 216, 230),
        'LightCoral' => array(240, 128, 128),
        'LightCyan' => array(224, 255, 255),
        'LightGoldenrodYellow' => array(250, 250, 210),
        'LightGreen' => array(144, 238, 144),
        'LightGrey' => array(211, 211, 211),
        'LightPink' => array(255, 182, 193),
        'LightSalmon' => array(255, 160, 122),
        'LightSeaGreen' => array(32, 178, 170),
        'LightSkyBlue' => array(135, 206, 250),
        'LightSlateGray' => array(119, 136, 153),
        'LightSteelBlue' => array(176, 196, 222),
        'LightYellow' => array(255, 255, 224),
        'Lime' => array(0, 255, 0),
        'LimeGreen' => array(50, 205, 50),
        'Linen' => array(250, 240, 230),
        'Magenta' => array(255, 0, 255),
        'Maroon' => array(128, 0, 0),
        'MediumAquamarine' => array(102, 205, 170),
        'MediumBlue' => array(0, 0, 205),
        'MediumOrchid' => array(186, 85, 211),
        'MediumPurple' => array(147, 112, 219),
        'MediumSeaGreen' => array(60, 179, 113),
        'MediumSlateBlue' => array(123, 104, 238),
        'MediumSpringGreen' => array(0, 250, 154),
        'MediumTurquoise' => array(72, 209, 204),
        'MediumVioletRed' => array(199, 21, 133),
        'MidnightBlue' => array(25, 25, 112),
        'MintCream' => array(245, 255, 250),
        'MistyRose' => array(255, 228, 225),
        'Moccasin' => array(255, 228, 181),
        'NavajoWhite' => array(255, 222, 173),
        'Navy' => array(0, 0, 128),
        'OldLace' => array(253, 245, 230),
        'Olive' => array(128, 128, 0),
        'OliveDrab' => array(107, 142, 35),
        'Orange' => array(255, 165, 0),
        'OrangeRed' => array(255, 69, 0),
        'Orchid' => array(218, 112, 214),
        'PaleGoldenrod' => array(238, 232, 170),
        'PaleGreen' => array(152, 251, 152),
        'PaleTurquoise' => array(175, 238, 238),
        'PaleVioletRed' => array(219, 112, 147),
        'PapayaWhip' => array(255, 239, 213),
        'PeachPuff' => array(255, 218, 185),
        'Peru' => array(205, 133, 63),
        'Pink' => array(255, 192, 203),
        'Plum' => array(221, 160, 221),
        'PowderBlue' => array(176, 224, 230),
        'Purple' => array(128, 0, 128),
        'Red' => array(255, 0, 0),
        'RosyBrown' => array(188, 143, 143),
        'RoyalBlue' => array(65, 105, 225),
        'SaddleBrown' => array(139, 69, 19),
        'Salmon' => array(250, 128, 114),
        'SandyBrown' => array(244, 164, 96),
        'SeaGreen' => array(46, 139, 87),
        'Seashell' => array(255, 245, 238),
        'Sienna' => array(160, 82, 45),
        'Silver' => array(192, 192, 192),
        'SkyBlue' => array(135, 206, 235),
        'SlateBlue' => array(106, 90, 205),
        'SlateGray' => array(112, 128, 144),
        'Snow' => array(255, 250, 250),
        'SpringGreen' => array(0, 255, 127),
        'SteelBlue' => array(70, 130, 180),
        'Tan' => array(210, 180, 140),
        'Teal' => array(0, 128, 128),
        'Thistle' => array(216, 191, 216),
        'Tomato' => array(255, 99, 71),
        'Turquoise' => array(64, 224, 208),
        'Violet' => array(238, 130, 238),
        'Wheat' => array(245, 222, 179),
        'White' => array(255, 255, 255),
        'WhiteSmoke' => array(245, 245, 245),
        'Yellow' => array(255, 255, 0),
        'YellowGreen' => array(154, 205, 50),
    );


    /**
     * This method will try to give you color names based on a provided color. It gives two values, one a CSS-named
     * color, the other a color in a tertiary color wheel.
     * @param $color array of RGB decimal values 0-255, or hex color string
     * @param bool $equalizeLuminosity for color wheel results, try to find closest primary/secondary color
     * @return array two color names, with keys 'css' and 'wheel'
     */
    public static function closest($color,$equalizeLuminosity=false)
    {
        $dist = 999;
        $close = '';
        if (is_array($color))
            $triplet = $color;
        else
            $triplet = self::hexToTriplet($color);
        foreach(self::$cssNamedColors as $col=> $ctrip)
        {
            $cdist = sqrt(
                pow($triplet[0]-$ctrip[0],2) +
                pow($triplet[1]-$ctrip[1],2)+
                pow($triplet[2]-$ctrip[2],2));
            if ($cdist < $dist)
            {
                $dist = $cdist;
                $close = $col;
            }
        }
        $hdist = 999;
        $hclose = '';
        $hsl = self::rgb2hsv($triplet);
        if ($hsl[1]<0.1)
        {
            // mono
            foreach(self::$grays as $col=>$val)
                if (abs($val - $hsl[2]) < $hdist)
                {
                    $hdist = abs($val - $hsl[2]);
                    $hclose = $col;
                }
        }
        else
        {
            // color
            foreach(self::$colorWheelHue as $col=>$val)
            {
                 if (abs($val - $hsl[0]) < $hdist)
                {
                $hdist = abs($val - $hsl[0]);
                $hclose = $col;
                }

            }
        }
        return array('wheel'=>$hclose,'css'=>$close);
    }

    /**
     * Parse a hex string into an RGB triplet, e.g., #ff0000 -> array(255,0,0)
     * @param $hex
     * @return array
     */
    public static function hexToTriplet($hex)
    {
        $hex = str_replace(array('0x','#',','),array('','',''),$hex);
        $hexes = str_split($hex, 2);
        return array_map(function($h){return hexdec($h);},$hexes);
    }

    /**
     * Convert RGB values (0-255) into HSV values (degrees, percent). From the Wikipedia alogrithm.
     * @param $triplet array of R,G,B values in range 0-255
     * @return array array of HSV values (hue 0-365, saturation 0-100, value 0-100)
     */
    public static function rgb2hsv($triplet)
    {
        $r = $triplet[0]/255;
        $g = $triplet[1]/255;
        $b = $triplet[2]/255;
        $min = min($r, $g, $b);
        $max = max($r, $g, $b);
        $val = $max - $min;

        if ($val == 0)
        {
            $hue = 0;
            $sat = 0;
        }
        else
        {
            $sat = $val/$max;
            $scr = (($max - $r)/6 + $val/2)/$val;
            $scg = (($max - $g)/6 + $val/2)/$val;
            $scb = (($max - $b)/6 + $val/2)/$val;

            if ($r == $max)
                $hue = $scb - $scg;
            else if ($g == $max)
                $hue = 1/3 + $scr - $scb;
            else if ($b == $max)
                $hue = 2/3 + $scg - $scr;

            if ($hue<0) $hue+=1;
            if ($hue>=1) $hue-=1;
        }

        $hue = round($hue*365);
        return array($hue,$sat,$max);
    }
}
