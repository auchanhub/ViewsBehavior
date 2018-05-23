<?php

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\Controller;
use yii\db\Expression;
use yii\db\Exception;
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
     * @var string
     */
    public $targetModel = '';

    /**
     * Имя модели хранения просмотров
     *
     * @var string
     */
    public $viewsModel = '';

    /**
     * Имя атрибута хранящий timestamp создания записи модели хранения просмотров
     *
     * @var string
     */
    public $createTimeAttribute = 'created_at';

    /**
     * Namespace модели хранения просмотров
     *
     * @var string
     */
    public $viewsModelNamespace = 'common\models';

    /**
     * Namespace модели просмотры которого собираемся учитывать
     *
     * @var string
     */
    public $modelNamespace = 'common\models';

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
         * Получаем модель учета просмотров и модель просмотры которого надо учитывать соответственно
         *
         * @var ActiveRecord $viewsModelName
         * @var ActiveRecord $targetModelName
         */
        $viewsModelName = '\\'.$this->viewsModelNamespace.'\\'.$this->viewsModel;
        $targetModelName = '\\'.$this->modelNamespace.'\\'.$this->targetModel;

        /**
         * Поиск просматриваемой модели по атрибуту и значению атрибута который зашел в экшен
         * Обрываем выполнение если не нашлась модель
         *
         * @var null|ActiveRecord $model
         */
        $model = $targetModelName::findOne(
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

        $transaction = Yii::$app->getDb()->beginTransaction();

        try {

            //Ставим куки о просмотре текущей модели
            $cookie = new Cookie([
                'name' => $this->cookieName,
                'value' => $model->id,
                'expire' => time() + $this->cookieExpireTime,
            ]);
            Yii::$app->getResponse()->getCookies()->add($cookie);

            /**
             * Ищем запись о просмотре модели за сегодня
             * Если не нашли создаем новую иначе апдейтим счетчик у найденной
             * @var null|ActiveRecord $modelViews
             */
            $modelViews = $viewsModelName::find()
                ->where(['model_id' => $model->id])
                ->andWhere(['>', 'created_at', new Expression('UNIX_TIMESTAMP(CURDATE())')])
                ->andWhere(['<', 'created_at', new Expression('UNIX_TIMESTAMP(CURDATE() + 1)')])
                ->limit(1)
                ->orderBy(['id'=>SORT_DESC])
                ->one();

            if (null === $modelViews) {
                $modelViews = new $viewsModelName;
                $modelViews->views = 1;
                $modelViews->model_id = $model->id;
                $modelViews->save();
            } else {
                $modelViews->updateCounters(['views' => 1]);
                $modelViews->updated_at = new Expression('NOW()');
                $modelViews->save();
            }

            $transaction->commit();

        } catch (Exception $e) {
            $transaction->rollback();
            Yii::warning($e->__toString(), 'ViewBehaviors.add.updateCounters');
        }

    }
}