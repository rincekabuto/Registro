<?php

namespace Controller;

class Index extends AbstractController
{
    public function indexAction()
    {
        $user = \User::getByToken($this->getToken());
        if ($user) {
            $this->homeAction($user);
        }else{
            $this->welcomeAction();
        }
    }
    public function registerAction()
    {
        $formValidator = new \Form\Validator($_POST);
        $formValidator->name('name')->required()->alfa();
        $formValidator->name('email')->required()->email();
        $formValidator->name('password')->required();

        $fields = $formValidator->extractFields();
        $fieldsValues = $formValidator->extractFieldsValues();

        $fileUploader = new \File\Uploader(['jpg','jpeg','png','gif'], 2097152, ['image/png','image/jpeg','image/gif']);
        $fileUploader->savePath = __DIR__.'/../../web/avatars/';

        if ($formValidator->isGroupValid()){
            if($fileUploader->upload()){
                $avatarArr = ['avatar' => $fileUploader->getUploadFileInfo()[0]['savename']];
            }else{
                $avatarArr = ['avatar' => ''];
            }
            $fieldsValues = array_merge($fieldsValues, $avatarArr);
            try {
                $user = \User::createFromArray($fieldsValues);
            }catch(\ErrorException $e){
                var_dump($e);
                $registerErrors = serialize(
                    array_merge(
                        $fields,
                        ['email' => ['value' => $user->email, 'error' => 'User with that email already exist.']]
                    )
                );
                header("Location: http://".$_SERVER['HTTP_HOST'].'/?registerErrors='.$registerErrors);
            }
            $user->login();
            header("Location: http://".$_SERVER['HTTP_HOST'].'/');
        }else{
            $registerErrors = serialize(
                array_merge(
                    $fields,
                    ['avatar' => ['error' => $fileUploader->getErrorMsg()]]
                )
            );
            header("Location: http://".$_SERVER['HTTP_HOST'].'/?registerErrors='.$registerErrors);
        }
    }
    public function authorizeAction()
    {
        $formValidator = new \Form\Validator($_POST);
        $formValidator->name('email')->required()->email();
        $formValidator->name('password')->required();

        $fields = $formValidator->extractFields();
        $fieldsValues = $formValidator->extractFieldsValues();

        if ($formValidator->isGroupValid()){
            $user = \User::getByCredentials($fieldsValues['email'], $fieldsValues['password']);
            if($user){
                if($fieldsValues && array_key_exists('rememberMe', $fieldsValues)){
                    $rememberMe = true;
                }else{
                    $rememberMe = false;
                }
                $user->login($rememberMe);
                header("Location: http://".$_SERVER['HTTP_HOST'].'/');
            }else{
                $authorizeErrors = serialize(
                    array_merge(
                        ['under' => ['error' => 'Wrong email password pair. <br/> Check the fields']],
                        $fields
                    )
                );
                header("Location: http://".$_SERVER['HTTP_HOST'].'/?authorizeErrors='.$authorizeErrors);
            }
        }else{
            $authorizeErrors = serialize($fields);
            header("Location: http://".$_SERVER['HTTP_HOST'].'/?authorizeErrors='.$authorizeErrors);
        }
    }
    protected function welcomeAction()
    {
        $params = [];
        if(array_key_exists('registerErrors', $_GET) && $_GET['registerErrors'] != null){
            $params['registerErrors'] = unserialize($_GET['registerErrors']);
        }
        if(array_key_exists('authorizeErrors', $_GET) && $_GET['authorizeErrors'] != null){
            $params['authorizeErrors'] = unserialize($_GET['authorizeErrors']);
        }
        print $this->render('welcome', $params);
    }
    protected function homeAction(\User $user)
    {
        $userData = $user->extract();
        print $this->render('home', ['user' => $userData]);
    }
    public function logoutAction()
    {
        $token = $this->getToken();
        if($token) {
            $user = \User::getByToken($token);
            if ($user) {
                $user->logout();
            }
        }
        header('Location:http://'.$_SERVER['HTTP_HOST'].'/');
    }
    private function getToken(){
        $token = null;
        if(array_key_exists('token', $_SESSION) && $_SESSION['token'] != null){
            $token = $_SESSION['token'];
        }
        if(array_key_exists('token', $_COOKIE) && $_COOKIE['token'] != null){
            $token = $_COOKIE['token'];
        }
        if(array_key_exists('token', $_GET) && $_GET['token'] != null){
            $token = $_GET['token'];
        }
        return $token;
    }
}