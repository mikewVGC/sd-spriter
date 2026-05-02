<?php
//
// A Very Simple PHP Image Manipulation Class
//
// Copyright (C) 2014, Mike W.
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this
// software and associated documentation files (the "Software"), to deal in the Software
// without restriction, including without limitation the rights to use, copy, modify, merge,
// publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
// to whom the Software is furnished to do so.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
// INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
// PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
// FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
// ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.
//
//
// Usage examples:
//
// Create a new image:
// $image = Image::create(640, 480);
//
// Load an image from the disk (second argument can be left off):
// $image = Image::load('/tmp/image.png', 'png');
//
// Manipulate the image:
// $image->resize(320, 0); // resize to 320 wide, maintaining aspect ratio
// $image->rotate(180); // rotate 180 degrees clockwise
// $image->crop(0, 0, 200, 200); // crop a 200px square out from the upper left
//
// Output the image directly:
// $image->output('jpg');
//
// Save the image to disk:
// $image->write('/tmp/new_image.jpg', 'jpg', 90); // third argument is optional quality (for jpg only)
//
// Clean up:
// $image->destroy();
//

class Image
{
    const DEFAULT_JPG_QUALITY = 80; // jpg quality is from 0 - 100

    private $imageData;

    private $transparent = false;

    private $width;
    private $height;

    private $fonts = [];

    private $filters = [
        'negate'       => IMG_FILTER_NEGATE,
        'grayscale'    => IMG_FILTER_GRAYSCALE,
        'greyscale'    => IMG_FILTER_GRAYSCALE,
        'brightness'   => IMG_FILTER_BRIGHTNESS,
        'contrast'     => IMG_FILTER_CONTRAST,
        'colorize'     => IMG_FILTER_COLORIZE,
        'edgeDetect'   => IMG_FILTER_EDGEDETECT,
        'emboss'       => IMG_FILTER_EMBOSS,
        'gaussianBlur' => IMG_FILTER_GAUSSIAN_BLUR,
        'blur'         => IMG_FILTER_SELECTIVE_BLUR,
        'meanRemoval'  => IMG_FILTER_MEAN_REMOVAL,
        'smooth'       => IMG_FILTER_SMOOTH,
        'pixelate'     => IMG_FILTER_PIXELATE,
    ];

    private function __construct($imageData, $transparent = false)
    {
        $this->imageData = $imageData;
        $this->transparent = $transparent;
        $this->setWidth();
        $this->setHeight();

        if ($imageData === false) {
            throw new \Exception("Invalid image");
        }
    }

    public function getImageData()
    {
        return $this->imageData;
    }

    private function setWidth()
    {
        $this->width = imagesx($this->imageData);
    }

    private function setHeight()
    {
        $this->height = imagesy($this->imageData);
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    // create a new image (returns a new Image)
    public static function create($width, $height, $transparent = false)
    {
        if(!$width || !$height) {
            throw new \Exception('Image cannot have dimensions of 0px');
        }

        $data = imagecreatetruecolor($width, $height);

        static::makeTransparent($data);

        return new Image($data, $transparent);
    }

    public static function getTypeFromFilename($filename)
    {
        return substr(strtolower(strrchr($filename, '.')), 1);
    }

    // load an image from disk (returns a new Image)
    public static function load($path, $type = '', $transparent = false)
    {
        if(!$type) {
            $type = self::getTypeFromFilename($path);
        }

        if(!file_exists($path)) {
            return null;
        }

        switch($type) {
            case 'jpg':
            case 'jpeg':
                $data = imagecreatefromjpeg($path);
                break;
            case 'png':
                if ($transparent) {
                    $pngData = imagecreatefrompng($path);
                    $w = imagesx($pngData);
                    $h = imagesy($pngData);
                    $data = imagecreatetruecolor($w, $h);
                    static::makeTransparent($data);
                    imagecopyresampled($data, $pngData, 0, 0, 0, 0, $w, $h, $w, $h);
                } else {
                    $data = imagecreatefrompng($path);
                }
                break;
            case 'gif':
                $data = imagecreatefromgif($path);
                break;
            default:
                throw new \Exception('Unknown image format');
        }

        return new Image($data, $transparent);
    }

    // load from a string (base64 decoded)
    public static function loadFromString($string)
    {
        $data = imagecreatefromstring($string);
        if ($data === false) {
            return false;
        }
        return new Image($data);
    }

    public static function makeTransparent(&$imgData)
    {
        imagesavealpha($imgData, true);
        imagefill($imgData, 0, 0, imagecolorallocatealpha($imgData, 255, 255, 255, 127));
    }

    public function setAlphaBlending($mode)
    {
        imagealphablending($this->imageData, $mode);
    }

    public function setOverlayBlending()
    {
        imagelayereffect($this->imageData, IMG_EFFECT_OVERLAY);
    }

    // resize the current image to specified width/height
    // if width/height is 0 and the other one is not the proportions will be maintained
    public function resize($width = 0, $height = 0)
    {
        // calculate width/height
        if(!$width) {
            $width = floor($this->width * ($height / $this->height));
        }

        if(!$height) {
            $height = floor($this->height * ($width / $this->width));
        }

        if(!$width || !$height) {
            throw new \Exception('Image cannot be resized to 0px');
        }

        $dest = imagecreatetruecolor($width, $height);

        if ($this->transparent) {
            static::makeTransparent($dest);
        }

        imagecopyresampled(
            $dest,
            $this->imageData,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->width,
            $this->height
        );

        imagedestroy($this->imageData);
        $this->imageData = $dest;
        $this->setWidth();
        $this->setHeight();
        return true;
    }

    // resize the current image by a percent (expected as
    // range 0.0 - 1.0, but higher numbers can be used)
    public function resizePct($pct)
    {
        return $this->resize(
            floor($this->width * $pct),
            floor($this->height * $pct)
        );
    }

    // crop out a portion of the current image (discard the rest)
    public function crop($x, $y, $width, $height)
    {
        $dest = imagecreatetruecolor($width, $height);

        if ($this->transparent) {
            static::makeTransparent($dest);
        }

        imagecopy(
            $dest,
            $this->imageData,
            0,
            0,
            $x,
            $y,
            $width,
            $height
        );

        imagedestroy($this->imageData);
        $this->imageData = $dest;
        $this->setWidth();
        $this->setHeight();

        return true;
    }

    // rotate the current image
    public function rotate($degrees)
    {
        $this->imageData = imagerotate(
            $this->imageData,
            -$degrees, // this is 'anti-clockwise' for some reason
            imagecolorallocatealpha($this->imageData, 255, 255, 255, 127)
        );

        $this->setWidth();
        $this->setHeight();

        return true;
    }

    // insert another Image into this one at x/y
    public function insert($image, $x, $y)
    {
        imagecopyresampled(
            $this->imageData,
            $image->getImageData(),
            $x,
            $y,
            0,
            0,
            $image->getWidth(),
            $image->getHeight(),
            $image->getWidth(),
            $image->getHeight()
        );
        return true;
    }

    public function registerFont($name, $file)
    {
        if (!file_exists($file)) {
            throw new \Exception("Could not find file for font {$name}");
        }

        $this->fonts[$name] = $file;
    }

    public function writeText($text, $color, $font, $size, $x, $y)
    {
        $bbox = imagettftext(
            $this->imageData,
            $size,
            0,
            $x,
            $y,
            $color,
            $this->fonts[$font],
            $text
        );

        return $bbox;
    }

    public function writeTextRight($text, $color, $font, $size, $x, $y)
    {
        $dims = $this->writeTextDimensions($text, $font, $size);
        $x -= $dims['width'];

        return $this->writeText($text, $color, $font, $size, $x, $y);
    }

    public function writeTextDimensions($text, $font, $size)
    {
        $bbox = imagettfbbox(
            $size,
            0,
            $this->fonts[$font],
            $text
        );

        return array(
            'x' => $bbox[6],
            'y' => $bbox[7],
            'width' => $bbox[4] - $bbox[6],
            'height' => $bbox[1] - $bbox[7],
        );
    }

    public function writeTextDebug($text, $color, $size, $x, $y)
    {
        return imagestring(
            $this->imageData,
            $size,
            $x,
            $y,
            $text,
            $color
        );
    }

    public function drawRectangle($x, $y, $width, $height, $color)
    {
        imagefilledrectangle(
            $this->imageData,
            $x,
            $y,
            $x + $width,
            $y + $height,
            $color
        );
    }

    public function drawElipse($x, $y, $width, $height, $color)
    {
        imagefilledellipse(
            $this->imageData,
            $x,
            $y,
            $width,
            $height,
            $color
        );
    }

    public function drawArc($cx, $cy, $width, $height, $startAngle, $endAngle, $color, $style = IMG_ARC_PIE)
    {
        imagefilledarc(
            $this->imageData,
            $cx,
            $cy,
            $width,
            $height,
            $startAngle,
            $endAngle,
            $color,
            $style
        );
    }

    public function floodFill($color, $x, $y)
    {
        imagefill(
            $this->imageData,
            $x,
            $y,
            $color
        );
    }

    public function fillToBorder($color, $x, $y, $borderColor)
    {
        imagefilltoborder(
            $this->imageData,
            $x,
            $y,
            $borderColor,
            $color
        );
    }

    public function floodTexture($image, $x, $y)
    {
        imagesettile(
            $this->imageData,
            $image->getImageData()
        );
        $this->floodFill(IMG_COLOR_TILED, $x, $y);
    }

    // run a filter on the image
    public function filter($filterName)
    {
        $args = func_get_args();
        array_shift($args);

        if (!isset($this->filters[$filterName])) {
            return false;
        }

        $filter = $this->filters[$filterName];
        $args = array_merge([ $this->imageData, $filter ], $args);

        return call_user_func_array('imagefilter', $args);
    }

    public function affine($matrix, $clip = [])
    {
        $this->imageData = imageaffine($this->imageData, $matrix, $clip);
    }

    public function getColorAt($x, $y)
    {
        return imagecolorat($this->imageData, $x, $y);
    }

    public function setColorAt($color, $x, $y)
    {
        return imagesetpixel($this->imageData, $x, $y, $color);
    }

    // output an image to a stream (type is required)
    public function output($type, $quality = false)
    {
        if($quality === false) {
            $qualuty = self::DEFAULT_JPG_QUALITY;
        }
        header("Content-type: image/{$type}");
        $this->write(null, $type, $quality);
    }

    // write the current image to disk
    public function write($filename, $type, $quality = false)
    {
        if($quality === false) {
            $quality = self::DEFAULT_JPG_QUALITY;
        }

        $result = false;
        switch($type) {
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($this->imageData, $filename, $quality);
                break;
            case 'png':
                $result = imagepng($this->imageData, $filename);
                break;
            case 'gif':
                $result = imagegif($this->imageData, $filename);
                break;
            default:
                throw new \Exception('Unknown image format');
        }

        return $result;
    }

    // destroy the current image data
    public function destroy()
    {
        imagedestroy($this->imageData);
        return true;
    }
}
