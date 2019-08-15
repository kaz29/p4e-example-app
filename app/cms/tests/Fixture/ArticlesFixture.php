<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ArticlesFixture
 */
class ArticlesFixture extends TestFixture
{
    public $import = ['table' => 'articles'];

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
