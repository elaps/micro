<?php

namespace App\controllers;

class SiteController {
    public function actionIndex($id=2) {
        echo 'Hello World_'.$id;
    }
    public function actionTest() {
        echo 'Hello World2';
    }
    public function actionNew($id,$name) {
        echo 'Hello New '.$id.' '.$name;
    }
}