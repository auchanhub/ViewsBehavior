<?php

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\Controller;
use yii\db\Expression;
use yii\web\Cookie;


/**
 * Class ViewsBehavior
 * Активирует счетчик статистики по событию EVENT_BEFORE_ACTION
 * для указанных действий контроллера в методе behaviors()
 * @package common\behaviors
 */
class ViewsBehavior extends Behavior
{
    /**
     * Имя модели просмотры которого необходимо учитывать
     *
     * @var ActiveRecord
     */
    public $targetModel;

    /**
     * Имя модели хранения просмотров
     *
     * @var ActiveRecord
     */
    public $viewsModel;

    /**
     * Имя атрибута хранящий timestamp создания записи модели хранения просмотров
     *
     * @var string
     */
    public $createTimeAttribute = 'created_at';


    /**
     * Ключ по которому сохранять запись в куки о просмотре модели
     *
     * @var string
     */
    public $cookieName = '';

    /**
     * Время на которое ставить куки о просмотре модели (по умолчанию 1 год)
     *
     * @var int
     */
    public $cookieExpireTime = 31536000;

    /**
     * По дефолту экшен view, можно указать свое имя экшена для просмотра модели
     *
     * @var string
     */
    public $action = 'view';

    /**
     * Привязка вызова метода add к событию
     *
     * @return array
     */
    public function events()
    {
        return [
            Controller::EVENT_AFTER_ACTION => 'add',
        ];
    }

    /**
     * Сохранение данных посетителя в БД
     *
     * @return void
     */
    public function add()
    {
        if (!$this->targetModel || !$this->viewsModel || !$this->cookieName) {
            Yii::warning(
                "Empty required parametr. targetModel => '{$this->targetModel}', viewsModel => '{$this->viewsModel}', cookieName => '{$this->cookieName}'",
                'ViewBehaviors.add'
            );
            return;
        }

        /** @var Controller $controller */
        $controller = $this->owner;

        /**
         * ID текущего действия
         * Обрываем если не тот контроллер (см. @property $action)
         *
         * @var string $action_name
         */
        $action_name = $controller->action->id;
        if($action_name != $this->action) {
            return;
        }

        /**
         * Получаем аттрибут и значение аттрибута используемого экшеном просмотра модели (см. @property $action)
         *
         * @var array $action_params
         */
        $action_params = $controller->actionParams;


        /**
         * Поиск просматриваемой модели по атрибуту и значению атрибута который зашел в экшен
         * Обрываем выполнение если не нашлась модель
         *
         * @var null|ActiveRecord $model
         */
        $model = $this->targetModel::findOne(
            [
                array_keys($action_params)[0] => array_values($action_params)[0]
            ]
        );

        if (null === $model) {
            return;
        }

        /** @var null|int $cookieValue */
        $cookieValue = Yii::$app->getRequest()->getCookies()->getValue($this->cookieName);

        //Не учитываем просмотры если уже смотрели текущую модель
        if ($cookieValue && $model->id == $cookieValue) {
            return;
        }

        try {

            //Ставим куки о просмотре текущей модели
            $cookie = new Cookie([
                'name' => $this->cookieName,
                'value' => $model->id,
                'expire' => time() + $this->cookieExpireTime,
            ]);
            Yii::$app->getResponse()->getCookies()->add($cookie);

            /** @var ActiveRecord $modelViews */
            $modelViews = new $this->viewsModel;
            $modelViews->model_id = $model->id;
            $modelViews->save();


        } catch (\Exception $e) {
            Yii::$app->errorHandler->logException($e);
        }

    }
}
