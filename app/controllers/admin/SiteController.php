<?php
namespace App\controllers\admin;
class SiteController {
    public function actionIndex($id=2) {
        echo 'Hello ADMIN World_'.$id;
    }
    public function actionTest() {
        echo 'Hello ADMIN Test';
    }
}