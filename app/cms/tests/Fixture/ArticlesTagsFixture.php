<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ArticlesTagsFixture
 */
class ArticlesTagsFixture extends TestFixture
{
    public $import = ['table' => 'articles_tags'];

    /**
     * Init method
     *
     * @return void
     */
    public function init()
    {
        $this->records = [
        ];
        parent::init();
    }
}
