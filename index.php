<?php
require_once('FilletIconByGD.php');

//http://www.zc520.cc/php/103.html

$icon = new FilletIcon(array(
	'text' => '龙',
	'radius' => 0,
	'iconWidth' => 128,
	'iconHeight' => 128,
	'bgImage' => 'images/5.png',
	'fgImage' => 'images/model_icon_discover.png',
	'rate' 	=> 0.618,
	'gradualMode' => 'vertical',
));

$icon->create();


