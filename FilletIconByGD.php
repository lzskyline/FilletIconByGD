<?php
/**
 * 生成圆角图标类
 */
class FilletIcon
{
	private $bgColor;//定义背景色
	private $fgColor;//定义前景色
	private $bgImage;//定义背景图
	private $fgImage;//定义前景图
	private $text;//定义图标上面的文字
	private $bgEffects;//定义背景图特效实际就是一张图
	private $iconWidth;//定义生成的图标的宽度
	private $iconHeight;//定义生成的图标的高度
	private $radius;//定义圆角的角度值
	private $font;//定义字体
	private $rate;//定义前景图在背景图的比率（以宽度与高度中最长的一边来计算比率）
	private $outputPicType;//定义输出图片的类型(例如：png,jpeg)
	private $outputMode;//定义输出模式（0:直接输出，1:输出文件到指定目录）
	private $outputPath;//定义输出到图片路径
	private $gradualMode;//定义当背景是色值的时候可以指定渐变的模式，如果为空则不渐变
	
	public function __construct($attr = array())
	{
		//初始化
		$this->init();
		//设置用户自定义的属性
		if(!empty($attr))
		{
			foreach ($this->getAttrNames() AS $name)
			{
				if(isset($attr[$name]))
				{
					if(in_array($name, array('bgColor','fgColor')))
					{
						$this->$name = $this->colorHxToRGB($attr[$name]);
					}
					else if($name == 'font')
					{
						$fontPath = dirname(__FILE__) . '/font/' . $attr[$name];
						if(!file_exists($fontPath))
						{
							continue;
						}
						$this->$name = $fontPath;
					}
					else if($name == 'rate')
					{
						if($attr[$name] <= 0 || $attr[$name] > 1)
						{
							continue;
						}
						$this->$name = $attr[$name];
					}
					else if($name == 'bgImage' || $name == 'fgImage')
					{
						if(!$this->isAvailablePic($attr[$name]))
						{
							continue;
						}
						$this->$name = $attr[$name];
					}
					else if($name == 'gradualMode')
					{
						if($attr[$name] && !in_array($attr[$name],$this->getGradualModes()))
						{
							continue;
						}
						$this->$name = $attr[$name];
					}
					else 
					{
						$this->$name = $attr[$name];
					}
				}
			}
		}
	}

	//初始化,设置一些默认值
	private function init()
	{
		$this->bgColor = $this->colorHxToRGB('#FFA142');
		$this->fgColor = $this->colorHxToRGB('#FFFFFF');
		$this->iconWidth = $this->iconHeight = 200;
		$this->radius = 30;
		$this->font = dirname(__FILE__) . '/font/pavilion.ttf';//默认兰亭黑简
		$this->rate = 0.8;//默认比率
		$this->outputPicType = 'png';//默认输出png
		$this->outputMode = 0;//默认直接输出
		$this->outputPath = '';
		$this->gradualMode = '';//默认不渐变
	}
	
	//属性列表
	private function getAttrNames()
	{
		return array(
			'bgColor',
			'fgColor',
			'bgImage',
			'fgImage',
			'text',
			'bgEffects',
			'iconWidth',
			'iconHeight',
			'radius',
			'font',
			'rate',
			'outputPicType',
			'outputMode',
			'outputPath',
			'gradualMode',
		);
	}
	
	//获取渐变色模式列表
	private function getGradualModes()
	{
		return array(
			'horizontal',
			'vertical',
			'ellipse',
			'circle',
			'circle2',
			'square',
			'diamond',
		);
	}
	
	//判断一个图片是否可用
	private function isAvailablePic($imagePath)
	{
		//首先查看所给的图片链接里面有没有?有的话就去除掉
		if(strpos($imagePath,'?'))
		{
		    $image = explode('?',$imagePath);
		    $imagePath = $image[0];
		}
		
		$imageArr = explode('.',$imagePath);
		$imageSuffix = end($imageArr);//取到最后的值;
		$availableImages = array('jpg','png','gif','jpeg');//允许的图片类型
		if(in_array($imageSuffix,$availableImages))
		{
			//测试该图片是否可用
			if(@fopen($imagePath,'r'))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 将16进制颜色值分解成RGB
	 * 例如：0xff0000 => array('r' => 255,'g' => 0,'b' => 0);
	 */
	private function colorHxToRGB($hexColor)
	{
		$color = str_replace('#', '', (string)$hexColor);
		if (strlen($color) > 3)
		{
			$rgb = array(
				'r' => hexdec(substr($color, 0, 2)),
				'g' => hexdec(substr($color, 2, 2)),
				'b' => hexdec(substr($color, 4, 2)),
			);
		}
		else 
		{
			$r = substr($color, 0, 1) . substr($color, 0, 1);
			$g = substr($color, 1, 1) . substr($color, 1, 1);
			$b = substr($color, 2, 1) . substr($color, 2, 1);
			$rgb = array(
				'r' => hexdec($r),
				'g' => hexdec($g),
				'b' => hexdec($b)
			);
		}
		return $rgb;
	}
	
	/**
	 * 将RGB颜色值转换成16进制
	 * 例如：array('r' => 255,'g' => 0,'b' => 0) => 0xff0000;
	 */
	private function colorRGBToHx($rgb = array())
	{
		list($r,$g,$b) = array_values($rgb);
	    if($r < 0 || $g < 0 || $b < 0 || $r > 255 || $g > 255|| $b > 255)
	    {
	        return false;
	    }
	    return "#".(substr("00".dechex($r),-2)).(substr("00".dechex($g),-2)).(substr("00".dechex($b),-2));
	}
	
	/**
	 * 生成圆角
	 * 这些圆角实际上通过在一个小正方形上画弧线得来的
	 */
	private function createRounderCorner($isTransfg = 0)
	{
		$img 		= imagecreatetruecolor($this->radius, $this->radius);// 创建一个正方形的图像
		$bgcolor 	= imagecolorallocate($img, 255, 255, 255);// 图像的背景白色
		$fgcolor 	= imagecolorallocate($img, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b']);
		imagefill($img, 0, 0, $bgcolor);
		imagefilledarc($img, $this->radius, $this->radius, $this->radius * 2, $this->radius * 2, 180, 270, $fgcolor, IMG_ARC_PIE);
		//将弧角图片的颜色设置为透明
		if($isTransfg)
		{
			imagecolortransparent($img, $fgcolor);
		}
		else 
		{
			imagecolortransparent($img, $bgcolor);
		}
		return $img;
	}
	
	//创建4个圆角
	private function create4RounderCorners($resource,$ltCorner)
	{
		//左上角
		imagecopymerge($resource, $ltCorner, 0, 0, 0, 0, $this->radius, $this->radius, 100);
		//左下角
		$lbCorner	= imagerotate($ltCorner, 90, 0);
		imagecopymerge($resource, $lbCorner, 0, $this->iconHeight - $this->radius, 0, 0, $this->radius, $this->radius, 100);
		//右上角
		$rbCorner	= imagerotate($ltCorner, 180, 0);
		imagecopymerge($resource, $rbCorner, $this->iconWidth - $this->radius, $this->iconHeight - $this->radius, 0, 0, $this->radius, $this->radius, 100);
		//右下角
		$rtCorner	= imagerotate($ltCorner, 270, 0);
		imagecopymerge($resource, $rtCorner, $this->iconWidth - $this->radius, 0, 0, 0, $this->radius, $this->radius, 100);
		return $resource;
	}
	
	/**
	 *创建长方形 
	 */
	private function createRectangle($width,$height)
	{
		$img = imagecreatetruecolor($width, $height);
		$bgColor = imagecolorallocate($img, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b']);//定义颜色
		imagefill($img, 0, 0, $bgColor);
		return $img;
	}
	
	//根据所给图片的类型来选择使用哪一种图片类型来创建画布
	private function selectPicType($filePath)
	{
		//获取图片的后缀
		$type = strtolower(strrchr($filePath, '.'));
		switch ($type)
		{
			case '.jpg':
			case '.jpeg':
						$resource = imagecreatefromjpeg($filePath);break;
			case '.png':
						$resource = imagecreatefrompng($filePath);break;
			case '.gif':
						$resource = imagecreatefromgif($filePath);break;
			default:$resource = imagecreatefromjpeg($filePath);break;
		}
		return $resource;
	}
	
	//输出
	private function output($resource)
	{
		switch ($this->outputPicType)
		{
			case 'jpg':
			case 'jpeg':
						$contentType = 'image/jpeg';
						$outFunc = 'imagejpeg';
						break;
			case 'png':
						$contentType = 'image/png';
						$outFunc = 'imagepng';
						break;
			case 'gif':
						$contentType = 'image/gif';
						$outFunc = 'imagegif';
						break;
			default:$contentType = 'image/png';
					$outFunc = 'imagepng';
					break;
		}
		
		if($this->outputMode)
		{
			$outFunc($resource,$this->outputPath);
		}
		else 
		{
			header('Content-Type: ' . $contentType);
			$outFunc($resource);
		}
		imagedestroy($resource);
	}
	
	//颜色渐变特效
	private function colorGradual($im,$direction,$start,$end)
	{
		switch($direction) 
		{
			case 'horizontal':
				$line_numbers = imagesx($im);
				$line_width = imagesy($im);
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($start));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($end));
				break;
			case 'vertical':
				$line_numbers = imagesy($im);
				$line_width = imagesx($im);
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($start));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($end));
				break;
			case 'ellipse':
				$width = imagesx($im);
				$height = imagesy($im);
				$rh=$height>$width?1:$width/$height;
				$rw=$width>$height?1:$height/$width;
				$line_numbers = min($width,$height);
				$center_x = $width/2;
				$center_y = $height/2;
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($end));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($start));
				imagefill($im, 0, 0, imagecolorallocate( $im, $r1, $g1, $b1 ));
				break;
			case 'ellipse2':
				$width = imagesx($im);
				$height = imagesy($im);
				$rh=$height>$width?1:$width/$height;
				$rw=$width>$height?1:$height/$width;
				$line_numbers = sqrt(pow($width,2)+pow($height,2));
				$center_x = $width/2;
				$center_y = $height/2;
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($end));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($start));
				break;
			case 'circle':
				$width = imagesx($im);
				$height = imagesy($im);
				$line_numbers = sqrt(pow($width,2)+pow($height,2));
				$center_x = $width/2;
				$center_y = $height/2;
				$rh = $rw = 1;
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($end));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($start));
				break;
			case 'circle2':
				$width = imagesx($im);
				$height = imagesy($im);
				$line_numbers = min($width,$height);
				$center_x = $width/2;
				$center_y = $height/2;
				$rh = $rw = 1;
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($end));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($start));
				imagefill($im, 0, 0, imagecolorallocate( $im, $r1, $g1, $b1 ));
				break;
			case 'square':
			case 'rectangle':
				$width = imagesx($im);
				$height = imagesy($im);
				$line_numbers = max($width,$height)/2;
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($end));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($start));
				break;
			case 'diamond':
				list($r1,$g1,$b1) = array_values($this->colorHxToRGB($end));
				list($r2,$g2,$b2) = array_values($this->colorHxToRGB($start));
				$width = imagesx($im);
				$height = imagesy($im);
				$rh=$height>$width?1:$width/$height;
				$rw=$width>$height?1:$height/$width;
				$line_numbers = min($width,$height);
				break;
			default:
		}
		
		for ( $i = 0; $i < $line_numbers; $i=$i+1+$this->step ) 
		{
			$old_r=$r;
			$old_g=$g;
			$old_b=$b;
			
			$r = ( $r2 - $r1 != 0 ) ? intval( $r1 + ( $r2 - $r1 ) * ( $i / $line_numbers ) ): $r1;
			$g = ( $g2 - $g1 != 0 ) ? intval( $g1 + ( $g2 - $g1 ) * ( $i / $line_numbers ) ): $g1;
			$b = ( $b2 - $b1 != 0 ) ? intval( $b1 + ( $b2 - $b1 ) * ( $i / $line_numbers ) ): $b1;
			
			if ( "$old_r,$old_g,$old_b" != "$r,$g,$b")
				$fill = imagecolorallocate( $im, $r, $g, $b );
			switch($direction) 
			{
				case 'vertical':
					imagefilledrectangle($im, 0, $i, $line_width, $i+$this->step, $fill);
					break;
				case 'horizontal':
					imagefilledrectangle( $im, $i, 0, $i+$this->step, $line_width, $fill );
					break;
				case 'ellipse':
				case 'ellipse2':
				case 'circle':
				case 'circle2':
					imagefilledellipse ($im,$center_x, $center_y, ($line_numbers-$i)*$rh, ($line_numbers-$i)*$rw,$fill);
					break;
				case 'square':
				case 'rectangle':
					imagefilledrectangle ($im,$i*$width/$height,$i*$height/$width,$width-($i*$width/$height), $height-($i*$height/$width),$fill);
					break;
				case 'diamond':
					imagefilledpolygon($im, array (
						$width/2, $i*$rw-0.5*$height,
						$i*$rh-0.5*$width, $height/2,
						$width/2,1.5*$height-$i*$rw,
						1.5*$width-$i*$rh, $height/2 ), 4, $fill);
					break;
				default:	
			}
		}
	}
	
	//生成图标
	public function create()
	{
		//创建画布(图片优先)
		if($this->bgImage)
		{
			//以图片作为画布
			$resource = $this->selectPicType($this->bgImage);
			$new_res  = imagecreatetruecolor($this->iconWidth, $this->iconHeight);
			list($oWidth, $oheight) = getimagesize($this->bgImage);//获取原图片的宽度与高度
			imagecopyresampled($new_res, $resource, 0, 0, 0, 0, $this->iconWidth, $this->iconHeight, $oWidth, $oWidth);
			$resource = $new_res;
			
			/***************************分别在正方形的四个边角画圆角,然后合成到画布上************************/
			if($this->radius > 0)
			{
				$ltCorner = $this->createRounderCorner(1);
				$resource = $this->create4RounderCorners($resource,$ltCorner);
			}
			/***************************分别在正方形的四个边角画圆角,然后合成到画布上************************/
		}
		else if(!$this->gradualMode)//如果不采用渐变色
		{
			/*************************************创建一块真彩画布*************************************/
			$resource	 = imagecreatetruecolor($this->iconWidth, $this->iconHeight);//创建一个正方形的图像
			$bgcolor	 = imagecolorallocate($resource, 0, 0, 0); //图像的背景
			imagecolortransparent($resource, $bgcolor);//设置为透明
			imagefill($resource, 0, 0, $bgcolor);//填充颜色
			/*************************************创建一块真彩画布*************************************/
			
			if($this->radius > 0)
			{
				/***************************分别在正方形的四个边角画圆角,然后合成到画布上************************/
				$ltCorner = $this->createRounderCorner();
				$resource = $this->create4RounderCorners($resource,$ltCorner);
				/***************************分别在正方形的四个边角画圆角,然后合成到画布上************************/
				
				/****************************************创建一个长方形,竖直的******************************/
				$rectWidth1 = $this->iconWidth - $this->radius * 2;
				$rectHeight1 = $this->iconHeight;
				$rect1 = $this->createRectangle($rectWidth1,$rectHeight1);
				imagecopymerge($resource, $rect1, $this->radius, 0, 0, 0, $rectWidth1, $rectHeight1, 100);
				/****************************************创建一个长方形,竖直的******************************/
				
				/****************************************创建一个长方形,横向的******************************/
				$rectWidth2 = $this->iconWidth;
				$rectHeight2 = $this->iconHeight - $this->radius * 2;
				$rect2 = $this->createRectangle($rectWidth2,$rectHeight2);
				imagecopymerge($resource, $rect2, 0, $this->radius, 0, 0, $rectWidth2, $rectHeight2, 100);
				/****************************************创建一个长方形,横向的******************************/
			}
		}
		else//再用渐变色 
		{
			//创建一个画布
			$resource = imagecreatetruecolor($this->iconWidth, $this->iconHeight);
			$this->colorGradual($resource,$this->gradualMode,'#000000',$this->colorRGBToHx($this->bgColor));
			
			/***************************分别在正方形的四个边角画圆角,然后合成到画布上************************/
			if($this->radius > 0)
			{
				$ltCorner = $this->createRounderCorner(1);
				$resource = $this->create4RounderCorners($resource,$ltCorner);
			}
			/***************************分别在正方形的四个边角画圆角,然后合成到画布上************************/
		}
		
		//设置前景图(图片优先)
		if($this->fgImage)
		{
			//获取图片的大小尺寸
			$fgImageSize = getimagesize($this->fgImage);
			$fgImageWidth = $fgImageSize[0];
			$fgImageHeight = $fgImageSize[1];
			$oriFgRes = $this->selectPicType($this->fgImage);//获取前景图
			
			if($fgImageWidth * $fgImageHeight > $this->iconWidth * $this->iconHeight)
			{
				$minBgBorder = ($this->iconWidth < $this->iconHeight)?$this->iconWidth:$this->iconHeight;
				//前景图标最后覆盖到背景图上必须是按比率的，原则上是不能大于背景图
				if($fgImageWidth > $fgImageHeight)
				{
					$fgDstWidth = $minBgBorder * $this->rate;
					$fgDstHeight = ($fgDstWidth * $fgImageHeight) / $fgImageWidth;
				}
				else 
				{
					$fgDstHeight = $minBgBorder * $this->rate;
					$fgDstWidth = ($fgDstHeight * $fgImageWidth) / $fgImageHeight;
				}

				$dstImageRes = imagecreatetruecolor($fgDstWidth, $fgDstHeight);
				imagecopyresampled($dstImageRes, $oriFgRes, 0, 0, 0, 0, $fgDstWidth, $fgDstHeight, $fgImageWidth, $fgImageHeight);
				
				//合成到背景图上
				$dstX = $this->iconWidth/2 - $fgDstWidth/2;
				$dstY = $this->iconHeight/2 - $fgDstHeight/2;
				imagecopymerge($resource, $dstImageRes, $dstX, $dstY, 0, 0, $fgDstWidth, $fgDstHeight, 100);
			}
			else 
			{
				//合成到背景图上
				$dstX = $this->iconWidth/2 - $fgImageWidth/2;
				$dstY = $this->iconHeight/2 - $fgImageHeight/2;
				imagecopy($resource, $oriFgRes, $dstX, $dstY, 0, 0, $fgImageWidth, $fgImageHeight);
			}
		}
		else if($this->text)
		{
			/****************************************增加水印文字*************************************/
			$textColor = imagecolorallocate($resource,255,255,255);
			$textSize = 60;//字体大小
			$fontarea = imagettfbbox($textSize,0,$this->font,$this->text);
			$textWidth = $fontarea[2] - $fontarea[0];
			$textHeight = $fontarea[1] - $fontarea[7];
			$textX = $this->iconWidth/2 - $textWidth/2;
			$textY = $this->iconHeight/2 + $textSize/2;
			imagettftext($resource, $textSize, 0, $textX, $textY, $textColor, $this->font,$this->text);
			/****************************************增加水印文字*************************************/
		}
		
		/**********************************************输出**************************************/
		$this->output($resource);
		/**********************************************输出**************************************/
	}
}
