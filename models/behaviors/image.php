<?php

/* 
 * Image for cakePHP 
 * comments, bug reports are welcome skie AT mail DOT ru 
 * @author Yevgeny Tomenko aka SkieDr 
 * @version 1.0.0.5 

files stored in structure 
/images/{models}/{$id}/{field}.ext

 */ 

class ImageBehavior extends ModelBehavior {

	var $settings = null;

	function setup(&$model, $config = array()) {
		$this->imageSetup(&$model, $config);
	}
	
	function imageSetup(&$model, $config = array()) {
		$settings = Set::merge(array(
		'baseDir'=> '',
		), $config);
		
		if (!isset($settings['fields'])) $settings['fields']=array();
		$fields=array();
		foreach($settings['fields'] as $key=>$value) {
			$field = ife(is_numeric($key), $value, $key);
			$conf = ife(is_numeric($key), array(), ife(is_array($value),$value,array()));
			//if (!is_array($field)) $field=array('name'=>$field);
			$conf=Set::merge(
			array (
				//'name'=> '',
				'thumbnail' => array('prefix'=>'thumb',
					         'create'=>false,
					         'width'=>'100',
 					         'height'=>'100',
 					         'aspect'=>true,
							 'square'=>false,
							 'allow_enlarge'=>true,
		                    ),
				'resize'=>null, // array('width'=>'100','heigth'=>'100'),		
				'versions' => array(
				),
			), $conf);
			foreach ($conf['versions'] as $id=>$version) {
				$conf['versions'][$id]=Set::merge(array(
 					         'aspect'=>true,
							 'allow_enlarge'=>false,
							'square'=>false,
		                    ),$version);				
			}
			if (is_array($conf['resize'])) {
				if (!isset($conf['resize']['aspect'])) $conf['resize']['aspect']=true;
				if (!isset($conf['resize']['allow_enlarge'])) $conf['resize']['allow_enlarge']=false;
			}
			$fields[$field]=$conf;
			
		}
		$settings['fields']=$fields;
		
		$this->settings[$model->name] = $settings;
		//debug($settings);
		//exit;
	}
	
	/**
	 * Before save method. Called before all saves
	 *
	 * Overriden to transparently manage setting the item position to the end of the list 
	 *
	 * @param AppModel $model
	 * @return boolean True to continue, false to abort the save
	 */ 
	function beforeSave(&$model) {
		//$model->log($this->settings);
		extract($this->settings[$model->name]);
		if (empty($model->data[$model->name][$model->primaryKey])) {
			//$this->__addToListBottom(&$model);
		}

		
		$tempData = array();
		foreach ($fields as $key=>$value) {
			$field = ife(is_numeric($key), $value, $key);
			if (isset($model->data[$model->name][$field])) {
				if ($this->__isUploadFile($model->data[$model->name][$field])) {
					$tempData[$field] = $model->data[$model->name][$field];
					$model->data[$model->name][$field]=$this->__getContent($model->data[$model->name][$field]);
				} else {
					unset($model->data[$model->name][$field]);
				}
			}
		}
			
		$this->runtime[$model->name]['beforeSave'] = $tempData; 		
		return true;
	} 

	function afterSave(&$model) {
		//$model->log($this->settings);
		extract($this->settings[$model->name]);
		if (empty($model->data[$model->name][$model->primaryKey])) {
			//$this->__addToListBottom(&$model);
		}

		$tempData = $this->runtime[$model->name]['beforeSave']; 
		unset($this->runtime[$model->name]['beforeSave']);
		foreach($tempData as $field=>$value) {
			$this->__saveFile(&$model, $field, $value);
		}
		
		return true;
	} 

	
	function __getParams(&$model, $field, $value, $fieldParams, $record) {
		extract($this->settings[$model->name]);
		$result=array();
		if ($value!='') {
			$folderName = $this->__getFolder(&$model, $record);
			$ext=$this->decodeContent($value);
			$fileName=$field .'.'. $ext;
			$result['path']=$folderName.$fileName;
			
			$thumb=$fields[$field]['thumbnail'];
			if ($thumb['create']) {
				$result['thumb']=$folderName.$this->__getPrefix($thumb).'_'.$fileName;
			}
			foreach($fields[$field]['versions'] as $version) {
				$result[$this->__getPrefix($version)]=$folderName.$this->__getPrefix($version).'_'.$fileName;
			}
		}
		return $result;
	}
	
	/**
	 * Before delete method. Called before all deletes
	 *
	 * Will delete the current item from list and update position of all items after one
	 *
	 * @param AppModel $model
	 * @return boolean True to continue, false to abort the delete
	 */ 
	function beforeDelete(&$model) {
		$this->runtime[$model->name]['ignoreUserAbort'] = ignore_user_abort();
		@ignore_user_abort(true);
		return true;
	} 

	function afterDelete(&$model) { 
		extract($this->settings[$model->name]);
		
		foreach ($fields as $field=>$fieldParams) {
			$folderPath=$this->__getFullFolder(&$model, $field);
			uses ('folder'); 
			$folder = &new Folder($path = $folderPath, $create = false);
			if ($folder!==false) {
				@$folder->delete($folder->pwd());
			}			
		}
		
		@ignore_user_abort((bool) $this->runtime[$model->name]['ignoreUserAbort']);
		unset($this->runtime[$model->name]['ignoreUserAbort']); 
		return true;
	} 	
	
	function __isUploadFile($file) {
		if (!isset($file['tmp_name'])) return false;
		return (file_exists($file['tmp_name']) && $file['error']==0);
	}

	function __getContent($file) {
		return $file['type'];
	}
	function decodeContent($content) {
		$contentsMaping=array(
	      "image/gif" => "gif",
	      "image/jpeg" => "jpg",
	      "image/pjpeg" => "jpg",
	      "image/x-png" => "png",
	      "image/jpg" => "jpg",
	      "image/png" => "png",
	      "application/x-shockwave-flash" => "swf",
	      "application/pdf" => "pdf",
	      "application/pgp-signature" => "sig",
	      "application/futuresplash" => "spl",
	      "application/msword" => "doc",
	      "application/postscript" => "ps",
	      "application/x-bittorrent" => "torrent",
	      "application/x-dvi" => "dvi",
	      "application/x-gzip" => "gz",
	      "application/x-ns-proxy-autoconfig" => "pac",
	      "application/x-shockwave-flash" => "swf",
	      "application/x-tgz" => "tar.gz",
	      "application/x-tar" => "tar",
	      "application/zip" => "zip",
	      "audio/mpeg" => "mp3",
	      "audio/x-mpegurl" => "m3u",
	      "audio/x-ms-wma" => "wma",
	      "audio/x-ms-wax" => "wax",
	      "audio/x-wav" => "wav",
	      "image/x-xbitmap" => "xbm",             
	      "image/x-xpixmap" => "xpm",             
	      "image/x-xwindowdump" => "xwd",             
	      "text/css" => "css",             
	      "text/html" => "html",                          
	      "text/javascript" => "js",
	      "text/plain" => "txt",
	      "text/xml" => "xml",
	      "video/mpeg" => "mpeg",
	      "video/quicktime" => "mov",
	      "video/x-msvideo" => "avi",
	      "video/x-ms-asf" => "asf",
	      "video/x-ms-wmv" => "wmv"
		);
		if (isset($contentsMaping[$content]))
			return $contentsMaping[$content];
		else return $content;
	}
	
	
	function __saveAs($fileData, $fileName=null, $folder) {
		
		if (is_writable($folder)) {
			if (is_uploaded_file($_FILES[$fileData]['tmp_name'])) 
			{
				if (empty($fileName)) $fileName = $_FILES[$fileData]['name'];
				copy($_FILES[$fileData]['tmp_name'], $folder.$fileName);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	function __getFolder(&$model, $record) {
		extract($this->settings[$model->name]);
		return  $baseDir .'/'. Inflector::camelize($model->name) .'/'. $record[$model->primaryKey] . '/';
	}
	function __getFullFolder(&$model, $field) {
		//return  WWW_ROOT .$this->__getFolder(&$model, $field);
		extract($this->settings[$model->name]);
		return  WWW_ROOT . IMAGES_URL. $baseDir .DS. Inflector::camelize($model->name) .DS. $model->id .DS;
	}
	
	function __saveFile(&$model, $field, $fileData) {
		//debug($fileData);
		extract($this->settings[$model->name]);
		$folderName = $this->__getFullFolder(&$model, $field);
		$ext=$this->decodeContent($this->__getContent($fileData));
		$fileName=$field .'.'. $ext;

		uses ('folder'); 
		uses ('file'); 
		$folder = &new Folder($path = $folderName, $create = true, $mode = '777');
		
		$files=$folder->find($fileName);
		
		$file= &new File($folder->pwd().DS.$fileName);
		
		$fileExists=($file!==false);
		if ($fileExists) { 
			@$file->delete();
		} 
		
		if (isset($fields[$field]['resize']['width']) && isset($fields[$field]['resize']['height'])) {	
			$file=$folder->pwd().DS.'tmp_'.$fileName;
			copy($fileData['tmp_name'], $file);
			$this->__resize($folder->pwd(),'tmp_'.$fileName,$fileName,$field, $fields[$field]['resize']);
			@unlink($file);			
		} else {		
			$file=$folder->pwd().DS.$fileName;
			copy($fileData['tmp_name'], $file);
		}

		
		
		if ($fields[$field]['thumbnail']['create']) {
			$fieldParams=$fields[$field]['thumbnail'];
			$newFile=$this->__getPrefix($fieldParams).'_'.basename($fileName);
			$this->__resize($folder->pwd(),$fileName,$newFile, $field, $fieldParams);
		}
		foreach($fields[$field]['versions'] as $version) {
			$fieldParams=$fields[$field]['thumbnail'];
			$newFile=$this->__getPrefix($version).'_'.basename($fileName);
			$this->__resize($folder->pwd(),$fileName,$newFile,$field, $version);
		
		}
		
		//$folder->pwd();
		//$fields[$field];
		//$fileData['tmp_name'], $fileData]['name']
	}
	
	
	function __getPrefix($fieldParams) {
		if (isset($fieldParams['prefix'])) {
			return $fieldParams['prefix'];
		} else {
			return $fieldParams['width'].'x'.$fieldParams['height'];
		}
	}
	
	/** 
	 * Automatically resizes an image and returns formatted IMG tag 
	 * 
	 * @param string $path Path to the image file, relative to the webroot/img/ directory. 
	 * @param integer $width Image of returned image 
	 * @param integer $height Height of returned image 
	 * @param boolean $aspect Maintain aspect ratio (default: true) 
	 * @param array    $htmlAttributes Array of HTML attributes. 
	 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT. 
	 * @return mixed    Either string or echos the value, depends on AUTO_OUTPUT and $return. 
	 * @access public 
	 */ 
    function __resize($folder, $originalName, $newName, $field, $fieldParams) { 
         
//		debug($fieldParams);die;

        $types = array(1 => "gif", "jpeg", "png", "swf", "psd", "wbmp"); // used to determine image type 
        $fullpath = $folder; 
     
        $url = $folder.DS.$originalName; 

		$x=$y=0; // default start coordinates
         
        if (!($size = getimagesize($url)))  
            return; // image doesn't exist 

		$w=$size[0];
		$h=$size[1];
		$width=$fieldParams['width'];
		$height=$fieldParams['height']; 
		
        if ($fieldParams['allow_enlarge']===false) { // don't enlarge image

			if (($width>$height)&&($width>$size[0])){
				$width=$size[0];
			}
			
			if (($height>$width)&&($height>$size[1])){
				$height=$size[1];
			}
			
/*			if (($width>$size[0])||($height>$size[1])) {
				$width=$size[0];
				$height=$size[1]; 
			}
*/
		} 		

		if ($fieldParams['square']) { // adjust to aspect. 
			if ($w>$h){
				$x=ceil(($w-$h)/2);
				$w=$h;
			}elseif($h>$w){
				$y=ceil(($h-$w)/2);					
				$h=$w;					
			}
			
        }elseif ($fieldParams['aspect']) { // adjust to aspect. 
			if ($w>$h){
				$height=ceil(($h*$width)/$w);
			}elseif($h>$w){
				$width=ceil(($w*$height)/$h);					
			}		
        }
  
		//$prefix=$this->__getPrefix($fieldParams);
        //$cachefile = $fullpath.DS.$prefix.'_'.basename($originalName);  // location on server 
        $cachefile = $fullpath.DS.$newName;  // location on server 
         
        if (file_exists($cachefile)) { 
            $csize = getimagesize($cachefile); 
            $cached = ($csize[0] == $width && $csize[1] == $height); // image is cached 
            if (@filemtime($cachefile) < @filemtime($url)) // check if up to date 
                $cached = false; 
        } else { 
            $cached = false; 
        } 
         
        if (!$cached) { 
            $resize = ($size[0] > $width || $size[1] > $height) || ($size[0] < $width || $size[1] < $height || ($fieldParams['allow_enlarge']===false)); 
        } else { 
            $resize = false; 
        } 
         
        if ($resize) { 
            $image = call_user_func('imagecreatefrom'.$types[$size[2]], $url); 
            if (function_exists("imagecreatetruecolor") && ($temp = imagecreatetruecolor ($width, $height))) { 
                imagecopyresampled ($temp, $image, 0, 0, $x, $y, $width, $height, $w, $h); 
              } else { 
                $temp = imagecreate ($width, $height); 
                imagecopyresized ($temp, $image, 0, 0, $x, $y, $width, $height, $size[0], $size[1]); 
            } 
            call_user_func("image".$types[$size[2]], $temp, $cachefile); 
            imagedestroy ($image); 
            imagedestroy ($temp); 
        }          
         
        //return $this->output(sprintf($this->Html->tags['image'], $relfile, $this->Html->parseHtmlOptions($htmlAttributes, null, '', ' ')), $return); 
    } 

	
}	
?>