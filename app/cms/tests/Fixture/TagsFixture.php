<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TagsFixture
 */
class TagsFixture extends TestFixture
{
    public $import = ['table' => 'tags'];

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
