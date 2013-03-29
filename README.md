YiimageThumb
============

Image ThumbNailer for the Yii framework

```
Save YiimageThumb.php to protected/components
```

```
Update protected/config/main.php

'components'=>array(
    'thumb'=>array(
  		'class'=>'YiimageThumb'
		),
    .... REST OF COMPONENTS ....
),
```

How To Use
==========

```
$image = Yii::app()->thumb->render(Yii::app()->basePath . '/../profiles/' . $model->image, array(
  	'width' => '250',
		'height' => '250',
		'link' => 'true',
		'hint' => 'false',
		//'crop' => 'false',
		'sharpen' => 'true',
		'longside' => '255',
    // Any $htmlOptions that can be used in CHtml::image()
		'imgOptions' => array('class' => 'thumb_image'),
		'imgAlt' => 'Test',
	));

echo $image;
```

Options
=======

```
image
width
height
longside
shortside
sharpen
cache
extrapolate
crop
imgAlt
imgOptions
```
