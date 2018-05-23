# ViewsBehavior

Поведение для учета просмотров ActiveRecord модели
----------------

Для подключения в требуемом контроллере переопределить метод behaviors()

Обязательный параметры
```php
use common\behaviors\ViewsBehavior;

public function behaviors()
{
    return [
        'statistics' => [
            'class' => ViewsBehavior::class,
            'targetModel' => \common\models\Article::class, //required default ''
            'viewsModel' => \common\models\ArticleViews::class, //required default ''
            'cookieName' => 'article_views', //required default ''
        ]
    ];
}
```

Возможные параметры
```php
use common\behaviors\ViewsBehavior;

public function behaviors()
{
    return [
        'statistics' => [
            'class' => ViewsBehavior::class,
            'targetModel' => \common\models\Article::class, //required default ''
            'viewsModel' => \common\models\ArticleViews::class, //required default ''
            'cookieName' => 'article_views', //required default ''
            'action' => 'view', //default view
            'createTimeAttribute' => 'created_at', //default created_at
            'cookieExpireTime' => 31536000, //default 31536000 (1 year)
        ]
    ];
}
```
