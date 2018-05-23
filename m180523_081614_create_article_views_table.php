<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%article_views}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%article}}`
 */
class m180523_081614_create_article_views_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%article_views}}', [
            'id' => $this->primaryKey(),
            'model_id' => $this->integer()->notNull(),
            'views' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);

        // creates index for column `model_id`
        $this->createIndex(
            'idx-article_views-model_id',
            '{{%article_views}}',
            'model_id'
        );

        // add foreign key for table `article`
        $this->addForeignKey(
            'fk-article_views-model_id',
            '{{%article_views}}',
            'model_id',
            '{{%article}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `article`
        $this->dropForeignKey(
            'fk-article_views-model_id',
            '{{%article_views}}'
        );

        // drops index for column `model_id`
        $this->dropIndex(
            'idx-article_views-model_id',
            '{{%article_views}}'
        );

        $this->dropTable('{{%article_views}}');
    }
}
