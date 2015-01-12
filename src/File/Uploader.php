<?php
namespace File;

class Uploader
{
    public $allowExts;
    public $allowTypes;
    public $savePath;
    public $maxSize;
    private $error;
    public $autoCheck=true;
    public $uploadReplace=false;
    private $uploadFileInfo;

    const ERROR_LOADING = 'File loading error';
    const ERROR_EXTENSION = 'Wrong file extension';
    const ERROR_MOVE = 'Can not move file';
    const ERROR_SAVE_PATH_EXIST ="Save path folder not exist";
    const ERROR_SAVE_PATH_WRITEABLE ="Save path is not writeable";
    const ERROR_UPLOADING = 'Error while uploading';
    const ERROR_ALLOWED_SIZE = 'File bigger than allowed size！';
    const ERROR_MIME = 'Wrong MIME type';
    const ERROR_IS_UPLOADED_FILE = 'Is not uploaded file';

    public function __construct($allowExts='',$maxSize='',$allowTypes='')
    {
        if(!empty($allowExts)){
            if(is_array($allowExts)){
                $this->allowExts=array_map('strtolower',$allowExts);
            }else{
                $this->allowExts=explode(',',strtolower($allowExts));
            }
        }
        if(!empty($maxSize) && is_numeric($maxSize)){
            $this->maxSize=$maxSize;
        }
        if(!empty($allowTypes)){
            if(is_array($allowTypes)){
                $this->allowTypes=array_map('strtolower',$allowTypes);
            }else{
                $this->allowTypes=explode(',',strtolower($allowTypes));
            }
        }
    }
    private function save($file)
    {
        $filename = $file['savepath'].$file['savename'];
        if(!$this->uploadReplace && is_file($filename)) {
            $this->error = static::ERROR_LOADING;
            return false;
        }
        if( in_array(strtolower($file['extension']),array('gif','jpg','jpeg','bmp','png','swf')) && false === getimagesize($file['tmp_name'])) {
            $this->error = static::ERROR_EXTENSION;
            return false;
        }
        if(!move_uploaded_file($file['tmp_name'], $filename)) {
            $this->error = static::ERROR_MOVE;
            return false;
        }
        return true;
    }
    public function upload($savePath='')
    {
        if(empty($savePath))
            $savePath=$this->savePath;
        $savePath=rtrim($savePath,'/').'/';
        if(!is_dir($savePath)){
            if(!mkdir($savePath)){
                $this->error = static::ERROR_SAVE_PATH_EXIST;
                return false;
            }
        }else{
            if(!is_writeable($savePath)){
                $this->error = static::ERROR_SAVE_PATH_WRITEABLE;
                return false;
            }
        }

        $fileInfo = array();
        $isUpload   = false;
        $files	 =	 $this->dealFiles($_FILES);
        foreach ($files as $key=>$file){
            if(!empty($file['name'])){
                $file['key'] = $key;
                $file['extension'] = $this->getExt($file['name']);
                $file['savepath'] = $savePath;
                $file['savename'] = $this->getSaveName($file);
                if($this->autoCheck) {
                    if(!$this->check($file))
                        return false;
                }
                if(!$this->save($file)) return false;
                unset($file['tmp_name'],$file['error']);
                $fileInfo[] = $file;
                $isUpload   = true;
            }
        }
        if($isUpload) {
            $this->uploadFileInfo = $fileInfo;
            return true;
        }else {
            $this->error = static::ERROR_UPLOADING;
            return false;
        }
    }
    private function dealFiles($files)
    {
        $fileArray = array();
        $n = 0;
        foreach($files as $file){
            if(is_array($file['name'])){
                $keys = array_keys($file);
                $count = count($file['name']);
                for($i=0;$i<$count;$i++){
                    foreach ($keys as $key)
                        $fileArray[$n][$key] = $file[$key][$i];
                    $n++;
                }
            }else{
                $fileArray[$n]=$file;
                $n++;
            }
        }
        return $fileArray;
    }
    private function getExt($filename)
    {
        $pathinfo = pathinfo($filename);
        return $pathinfo['extension'];
    }
    private function getSaveName($file)
    {
        $saveName = md5(uniqid()).'.'.$file['extension'];
        return $saveName;
    }
    private function check($file)
    {
        if($file['error']!==0){
            $this->error = static::ERROR_UPLOADING;
            return false;
        }
        if(!$this->checkSize($file['size'])){
            $this->error = static::ERROR_ALLOWED_SIZE;
            return false;
        }
        if(!$this->checkExt($file['extension'])){
            $this->error = static::ERROR_EXTENSION;
            return false;
        }
        if(!$this->checkType($file['type'])) {
            $this->error = static::ERROR_MIME;
            return false;
        }
        if(!$this->checkUpload($file['tmp_name'])) {
            $this->error = static::ERROR_IS_UPLOADED_FILE;
            return false;
        }
        return true;
    }
    private function checkSize($size)
    {
        return $size < $this->maxSize;
    }
    private function checkExt($extension)
    {
        if(!empty($this->allowExts))
            return in_array(strtolower($extension),$this->allowExts,true);
        return true;
    }
    private function checkType($type)
    {
        if(!empty($this->allowTypes))
            return in_array(strtolower($type),$this->allowTypes,true);
        return true;
    }
    private function checkUpload($filename)
    {
        return is_uploaded_file($filename);
    }
    public function getUploadFileInfo()
    {
        return $this->uploadFileInfo;
    }
    public function getErrorMsg()
    {
        return $this->error;
    }
}