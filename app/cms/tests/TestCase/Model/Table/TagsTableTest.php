<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Tag;
use App\Model\Table\TagsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeFabricate\Adaptor\CakeFabricateAdaptor;
use Fabricate\Fabricate;

/**
 * App\Model\Table\TagsTable Test Case
 */
class TagsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TagsTable
     */
    public $Tags;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Tags',
        'app.Users',
        'app.Articles',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Tags') ? [] : ['className' => TagsTable::class];
        $this->Tags = TableRegistry::getTableLocator()->get('Tags', $config);

        Fabricate::config(function ($config) {
            $config->adaptor = new CakeFabricateAdaptor([CakeFabricateAdaptor::OPTION_FILTER_KEY => true]);
        });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Tags);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->assertInstanceOf(TagsTable::class, $this->Tags);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $data = [];
        $entity = $this->Tags->newEntity($data);
        $errors = $entity->getErrors();
        $expected = [
            'title' => ['_required' => 'This field is required'],
        ];
        $this->assertEquals($expected, $errors);

        $data = [
            'title' => str_repeat('A', 192),
        ];
        $entity = $this->Tags->newEntity($data);
        $errors = $entity->getErrors();
        $expected = [
            'title' => ['maxLength' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);

        $data = [
            'title' => str_repeat('A', 191),
        ];
        $entity = $this->Tags->newEntity($data);
        $errors = $entity->getErrors();
        $expected = [];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        Fabricate::create('Tags', [
            'title' => 'test-tag-name',
        ]);

        $data = [
            'title' => 'test-tag-name',
        ];
        $entity = $this->Tags->newEntity($data);
        $result = $this->Tags->save($entity);
        $this->assertFalse($result);
        $errors = $entity->getErrors();
        $expected = [
            'title' => ['unique' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors, 'タグのタイトルが重複する場合エラーになること');

        $data = [
            'title' => 'test-tag-name-new',
        ];
        $entity = $this->Tags->newEntity($data);
        $result = $this->Tags->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(Tag::class, $result);
    }
}
