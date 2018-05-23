# ViewsBehavior
================

Для подключения в требуемом контроллере переопределить метод behaviors()

Обязательный параметры
```php
public function behaviors()
{
    return [
        'statistics' => [
            'class' => ViewsBehavior::class,
            'targetModel' => 'Article', //required default ''
            'viewsModel' => 'ArticleViews', //required default ''
            'cookieName' => 'article_views', //required default ''
        ]
    ];
}
```

Возможные параметры
```php
public function behaviors()
{
    return [
        'statistics' => [
            'class' => ViewsBehavior::class,
            'targetModel' => 'Article', //required default ''
            'viewsModel' => 'ArticleViews', //required default ''
            'cookieName' => 'article_views', //required default ''
            'action' => 'view', //default view
            'modelNamespace' => 'common\models', //default common\models
            'viewsModelNamespace' => 'common\models', //default common\models
            'createTimeAttribute' => 'created_at', //default created_at
            'cookieExpireTime' => 31536000, //default 31536000 (1 year)
        ]
    ];
}
```
