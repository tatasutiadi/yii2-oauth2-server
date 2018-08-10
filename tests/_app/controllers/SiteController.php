<?php

namespace app\controllers;

use tecnocen\oauth2server\filters\auth\CompositeAuth;
use yii\rest\Controller;

class SiteController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CompositeAuth::class,
                'actionScopes' => ['user' => 'user'],
            ],
        ]);
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        return \Yii::$app->user->identity;
    }

    public function actionUser()
    {
        return \Yii::$app->user->identity;
    }
}
