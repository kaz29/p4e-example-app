<?php
use Migrations\AbstractMigration;

class CreateCmsTutorialTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $this->table('users')
            ->addColumn(
                'email',
                'string',
                [
                    'default' => null,
                    'limit' => 255,
                    'null' => false,
                ]
            )
            ->addColumn(
                'password',
                'string',
                [
                    'default' => null,
                    'limit' => 255,
                    'null' => false,
                ]
            )
            ->addColumn(
                'created',
                'timestamp',
                [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ]
            )
            ->addColumn(
                'modified',
                'timestamp',
                [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ]
            )
            ->create();

        $this->table('articles')
            ->addColumn(
                'user_id',
                'integer',
                [
                    'default' => null,
                    'limit' => \Phinx\Db\Adapter\PostgresAdapter::PHINX_TYPE_INTEGER,
                    'null' => false,
                ]
            )
            ->addColumn(
                'title',
                'string',
                [
                    'default' => null,
                    'limit' => 255,
                    'null' => false,
                ]
            )
            ->addColumn(
                'slug',
                'string',
                [
                    'default' => null,
                    'limit' => 191,
                    'null' => false,
                ]
            )
            ->addColumn('body',
                'text',
                [
                    'default' => null,
                    'null' => false,
                ]
            )
            ->addColumn('published',
                'boolean',
                [
                    'default' => null,
                    'null' => false,
                ]
            )
            ->addColumn(
                'created',
                'timestamp',
                [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ]
            )
            ->addColumn(
                'modified',
                'timestamp',
                [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ]
            )
            ->addIndex(
                [
                    'slug'
                ],
                [
                    'unique' => true
                ]
            )
            ->addForeignKey(['user_id'], 'users')
            ->create();

        $this->table('tags')
            ->addColumn(
                'title',
                'string',
                [
                    'default' => null,
                    'limit' => 191,
                    'null' => false,
                ]
            )
            ->addColumn(
                'created',
                'timestamp',
                [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ]
            )
            ->addColumn(
                'modified',
                'timestamp',
                [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ]
            )
            ->addIndex(
                [
                    'title'
                ],
                [
                    'unique' => true
                ]
            )
            ->create();

        $this->table('articles_tags', ['id' => false, 'primary_key' => ['article_id', 'tag_id']])
            ->addColumn(
                'article_id',
                'integer',
                [
                    'default' => null,
                    'limit' => \Phinx\Db\Adapter\PostgresAdapter::PHINX_TYPE_INTEGER,
                    'null' => false,
                ]
            )
            ->addColumn(
                'tag_id',
                'integer',
                [
                    'default' => null,
                    'limit' => \Phinx\Db\Adapter\PostgresAdapter::PHINX_TYPE_INTEGER,
                    'null' => false,
                ]
            )
            ->addForeignKey(['article_id'], 'articles')
            ->addForeignKey(['tag_id'], 'tags')
            ->create();
    }
}
