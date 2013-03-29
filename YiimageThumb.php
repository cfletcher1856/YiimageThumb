<?php

class YiimageThumbException extends CException
{
}

class YiimageThumb{
    private $image = null;
    private $width = null;
    private $height = null;
    private $longside = null;
    private $shortside = null;
    private $sharpen = null;
    private $cache = null;
    private $extrapolate = true;
    private $crop = true;
    private $imgAlt = null;
    private $imgOptions = array();

    private $image_types = array('','.gif','.jpg','.png');

    private $image_source = array();
    private $thumb = array();


    public function init()
    {

    }

    public function render($image, $options = array())
    {
        if(!file_exists($image))
        {
            throw new YiimageThumbException("The image `$image` could not be found.");
        }
        $this->image = $image;

        $this->updateOptions($options);
        $this->setImageSource($options);
        $this->setThumbSource();

        // echo "<pre>";print_r($this->image_source);echo "</pre>";
        // echo "<pre>";print_r($this->thumb);echo "</pre>";

        return $this->createThumb();
    }

    private function updateOptions($options)
    {
        foreach($options as $option => $value)
        {
            $this->$option = $value;
        }

        if(is_null($this->cache))
        {
            $this->cache = substr($this->image, 0, strrpos($this->image, "/")) . '/thumb_cache/';
        }

        if(!is_dir($this->cache))
        {
            mkdir($this->cache);
        }

        if(!is_writable($this->cache))
        {
            throw new YiimageThumbException("Directory `{$this->cache}` is not writable");
        }

        if(!is_numeric($this->width) || !is_numeric($this->height))
        {
            throw new YiimageThumbException('Illegal width/height value');
        }
    }

    private function setImageSource($options)
    {
        $image_info = getimagesize($this->image);

        if($image_info[2] == '' || $image_info[2] > 3)
        {
            throw new YiimageThumbException("Image type unsupported");
        }

        $this->image_source['width'] = $image_info[0];
        $this->image_source['height'] = $image_info[1];
        $this->image_source['type'] = $image_info[2];
        $this->image_source['string'] = $image_info[3];
        $this->image_source['filename'] = basename($this->image);
        $this->image_source['modified'] = filemtime($this->image);
        $this->image_source['hash'] = md5($this->image . $this->image_source['modified'] . implode('', $options));
    }

    private function setThumbSource()
    {
        $this->thumb['offset_w'] = 0;
        $this->thumb['offset_h'] = 0;
        $this->thumb['width'] = $this->width;
        $this->thumb['height'] = $this->height;
        $this->thumb['type'] = $this->image_source['type'];

        if(is_numeric($this->longside))
        {
            if($this->image_source['width'] < $this->image_source['height'])
            {
                $this->thumb['height'] = $this->longside;
                $this->thumb['width'] = round($this->longside / ($this->image_source['height'] / $this->image_source['width']));
            }
            else
            {
                $this->thumb['width'] = $this->longside;
                $this->thumb['height'] = round($this->longside / ($this->image_source['width'] / $this->image_source['height']));
            }
        }
        elseif(is_numeric($this->shortside))
        {
            if($this->image_source['width'] < $this->image_source['height'])
            {
                $this->thumb['width'] = $this->shortside;
                $this->thumb['height'] = round($this->shortside / ($this->image_source['width'] / $this->image_source['height']));
            }
            else
            {
                $this->thumb['height'] = $this->shortside;
                $this->thumb['width'] = round($this->shortside / ($this->image_source['height'] / $this->image_source['width']));
            }
        }

        if($this->crop)
        {
            $width_ratio = $this->image_source['width'] / $this->thumb['width'];
            $height_ratio = $this->image_source['height'] / $this->thumb['height'];

            if($width_ratio > $height_ratio)
            {
                $this->thumb['offset_w'] = round(($this->image_source['width'] - $this->thumb['width'] * $height_ratio) / 2);
                $this->image_source['width'] = round($this->thumb['width'] * $height_ratio);
            }
            elseif($width_ratio < $height_ratio)
            {
                $this->thumb['offset_h'] = round(($this->image_source['height'] - $this->thumb['height'] * $width_ratio) / 2);
                $this->image_source['height'] = round($this->thumb['height'] * $width_ratio);
            }
        }

        if(!$this->extrapolate && $this->thumb['height'] > $this->image_source['height'] && $this->thumb['width'] > $this->image_source['width'])
        {
            $this->thumb['width'] = $this->image_source['width'];
            $this->thumb['height'] = $this->image_source['height'];
        }
    }

    private function createThumb()
    {
        $this->thumb['file'] = $this->cache.$this->image_source['hash'].$this->image_types[$this->thumb['type']];
        $this->thumb['string'] = 'width="'.$this->thumb['width'].'" height="'.$this->thumb['height'].'"';

        $file_url = str_replace(realpath(".") , '', $this->thumb['file']);
        $file_url = str_replace(array("\\","//"),array("/","/"), $file_url);

        if(file_exists($this->thumb['file'])){
            $img = CHtml::image($file_url, $this->imgAlt, $this->imgOptions);
            if($this->link)
            {
                $img_link = str_replace(realpath(".") , '', $this->image);
                $img_link = str_replace(array("\\","//"),array("/","/"), $img_link);

                $img = CHtml::link($img, $img_link);
            }

            return $img;
        }

        if ($this->image_source['type'] == 1) $this->image_source['image'] = imagecreatefromgif($this->image);
        if ($this->image_source['type'] == 2) $this->image_source['image'] = imagecreatefromjpeg($this->image);
        if ($this->image_source['type'] == 3) $this->image_source['image'] = imagecreatefrompng($this->image);

        if($this->thumb['width'] * 4 < $this->image_source['width'] && $this->thumb['height'] * 4 < $this->image_source['height'])
        {
            $tmp['width'] = round($this->thumb['width'] * 4);
            $tmp['height'] = round($this->thumb['height'] * 4);
            $tmp['image'] = imagecreatetruecolor($tmp['width'], $tmp['height']);

            imagecopyresized($tmp['image'], $this->image_source['image'], 0, 0, $this->thumb['offset_w'], $this->thumb['offset_h'], $tmp['width'], $tmp['height'], $this->image_source['width'], $this->image_source['height']);
            $this->image_source['image'] = $tmp['image'];
            $this->image_source['width'] = $tmp['width'];
            $this->image_source['height'] = $tmp['height'];

            $this->thumb['offset_w'] = 0;
            $this->thumb['offset_h'] = 0;
            unset($tmp['image']);
        }

        $this->thumb['image'] = imagecreatetruecolor($this->thumb['width'], $this->thumb['height']);
        imagecopyresampled($this->thumb['image'], $this->image_source['image'], 0, 0, $this->thumb['offset_w'], $this->thumb['offset_h'], $this->thumb['width'], $this->thumb['height'], $this->image_source['width'], $this->image_source['height']);

        if($this->sharpen)
        {
            $this->thumb['image'] = $this->unsharpMask($this->thumb['image'], 80, .5, 3);
        }

        if ($this->thumb['type'] == 1)
        {
            imagetruecolortopalette($this->thumb['image'], false, 256);
            imagegif($this->thumb['image'], $this->thumb['file']);
        }

        if ($this->thumb['type'] == 2)
        {
            if(!$this->quality){
                $this->quality = 80;
            }
            imagejpeg($this->thumb['image'], $this->thumb['file'], $this->quality);
        }

        if ($this->thumb['type'] == 3)
        {
            imagepng($this->thumb['image'], $this->thumb['file']);
        }

        imagedestroy($this->thumb['image']);
        imagedestroy($this->image_source['image']);

        $img = CHtml::image($file_url, $this->imgAlt, $this->imgOptions);
        if($this->link)
        {
            $img_link = str_replace(realpath(".") , '', $this->image);
            $img_link = str_replace(array("\\","//"),array("/","/"), $img_link);

            $img = CHtml::link($img, $img_link);
        }

        return $img;
    }

    private function unsharpMask($img, $amount, $radius, $threshold)
    {
        if($amount > 500)
        {
            $amount = 500;
        }
        $amount = $amount * 0.016;

        if($radius > 50)
        {
            $radius = 50;
        }
        $radius = $radius * 2;

        if($threshold > 255)
        {
            $threshold = 255;
        }

        $radius = abs(round($radius));

        if($radius == 0)
        {
            return $img;
            imagedestroy($img);
            break;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        $imgCanvas = $img;
        $imgCanvas2 = $img;
        $imgBlur = imagecreatetruecolor($w, $h);

        for ($i = 0; $i < $radius; $i++)
        {
            imagecopy ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1);
            imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50);
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333);
            imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25);
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333);
            imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25);
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 );
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667);
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50);
        }

        $imgCanvas = $imgBlur;

        for ($x = 0; $x < $w; $x++)
        {
            for ($y = 0; $y < $h; $y++)
            {
                $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
                $rOrig = (($rgbOrig >> 16) & 0xFF);
                $gOrig = (($rgbOrig >> 8) & 0xFF);
                $bOrig = ($rgbOrig & 0xFF);
                $rgbBlur = ImageColorAt($imgCanvas, $x, $y);
                $rBlur = (($rgbBlur >> 16) & 0xFF);
                $gBlur = (($rgbBlur >> 8) & 0xFF);
                $bBlur = ($rgbBlur & 0xFF);

                $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
                $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
                $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

                if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
                {
                    $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
                    ImageSetPixel($img, $x, $y, $pixCol);
                }
            }
        }

        return $img;
    }
}
