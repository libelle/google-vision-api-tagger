## Synopsis

This is a crufty, chaotic, mixed-up collection of code to use Google's Vision API to populate
keyword tags for a collection of images.

It uses the Google Vision API to get information on JPG or NEF images, and populates EXIF and
XMP Sidecars.

It allows you to pixelate faces before submission to Google.

It will label the dominant colors provided by Google with human-readable color names.

## Motivation

I have decades worth of photos (recently exported from the late lamented Aperture) in JPEG and Nikon NEF
format. Some of them were well tagged and labeled, others not so much.

Before I migrate to a new DAM solution, I wanted to improve the tagging. Google's Cloud Vision API does
some stuff remarkably well: it identifies objects, colors, logos, text, and some landmarks. So I thought
I'd throw all the pictures at it, and use their AI to help me tag.

Then I got paranoid about submitting all those people pictures. Who knows what aggregation/processing
Google would do with that data? So I added code to anonymize the people in the pictures before upload.

Then I thought, hey, it'd be cool to have color names instead of hex codes, so I created a class to
do simplistic mapping of color values names. You can match to the CSS-named color list, or to a more
limited primary/secondary/tertiary RGB color-wheel.

A few things that might be interesting to the desperate:
I couldn't find any good examples of doing something like an oval-shaped overlay using `gd` lib in PHP.
So the anonymizing feature here includes one way of doing that. If you want to pixelate out faces with
a nice oval in your own code, this could be a starting point.

Similarly, the color-to-name matcher. I couldn't find exactly what I wanted, so I wrote something.
For matching to CSS named colors, it uses the shortest vector in RGB space.
For the color wheel, it does it in HSV space, and uses closest hue angle (or brightness value for grays).

## Requirements

As mentioned, this is a ridiculous hodge-podge of different languages and libraries that should
be consolidated into something much more compact and streamlined. But it hasn't been.

Many of the components I used for this mess don't feature license details in their repos,
so out of an abundance of caution, I don't include the files themselves.

Thus, it depends on a whole heap of stuff:
* `exiv2` (http://www.exiv2.org)
* a Google Vision API Key ( https://cloud.google.com/vision/ )
* Python with the `opencv` module
* PHP with `gd` library
* `GoogleCloudVision.php` class for PHP ( https://github.com/thangman22/google-cloud-vision-php )
* theOpenCV `res10_300x300_ssd_iter_140000.caffemodel` file (https://github.com/opencv/opencv_3rdparty/tree/dnn_samples_face_detector_20170830)
* the OpenCV `deploy.prototxt.txt` file ( https://github.com/opencv/opencv/tree/master/samples/dnn/face_detector )
* `dcraw` ( https://www.cybercom.net/~dcoffin/dcraw/ ) if you're converting NEF files
* `cjpeg` ( https://github.com/mozilla/mozjpeg ) if you're converting NEF files

## Installation

Get all them there disparate things from the Requirements section installed.

Copy the `sample-config.php` to `config.php`, and update with your Google API key and command paths as necessary.

## Running
`php tagger.php -h` for help

## Disclaimer

Use this stuff at your own risk. _**Don't use it on your only copy of a picture!**_ The code shouldn't
cause any issues, but if there's one lesson from the world of software it's "TRUST NO PROGRAM!"

## License

Many of the components don't feature license details in their repos, so I don't include them -- hence the stupidly long requirements list above.

My code here is crap that you shouldn't consider reusing. But if you're going to persist in this
foolishness, by all means, go right ahead. Just don't blame me when it inadvertently opens the Fourth Seal.

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

## Contact
Why?

SjG <github@fogbound.net>

https://github.com/libelle/google-vision-api-tagger