<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Article;
use App\Model\Table\ArticlesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeFabricate\Adaptor\CakeFabricateAdaptor;
use Fabricate\Fabricate;

/**
 * App\Model\Table\ArticlesTable Test Case
 */
class ArticlesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ArticlesTable
     */
    public $Articles;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Users',
        'app.Tags',
        'app.Articles',
        'app.ArticlesTags',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Articles') ? [] : ['className' => ArticlesTable::class];
        $this->Articles = TableRegistry::getTableLocator()->get('Articles', $config);

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
        unset($this->Articles);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->assertInstanceOf(ArticlesTable::class, $this->Articles);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $data = [];
        $entity = $this->Articles->newEntity($data);
        $result = $entity->getErrors();
        $expected = [
            'title' => ['_required' => 'This field is required'],
            'body' => ['_required' => 'This field is required'],
            'published' => ['_required' => 'This field is required'],
        ];
        $this->assertEquals($expected, $result);

        $data = [
            'title' => str_repeat('A', 256),
            'body' => str_repeat('A', 256),
            'published' => 'A',
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $entity->getErrors();
        $expected = [
            'title' => ['maxLength' => 'The provided value is invalid'],
            'published' => ['boolean' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $result);

        $data = [
            'title' => str_repeat('A', 9),
            'body' => str_repeat('A', 9),
            'published' => false,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $entity->getErrors();
        $expected = [
            'title' => ['minLength' => 'The provided value is invalid'],
            'body' => ['minLength' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $result);

        $data = [
            'title' => str_repeat('A', 10),
            'body' => str_repeat('A', 10),
            'published' => false,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $entity->getErrors();
        $expected = [];
        $this->assertEquals($expected, $result);

        $data = [
            'title' => str_repeat('A', 255),
            'body' => str_repeat('A', 256),
            'published' => true,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $entity->getErrors();
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        Fabricate::create('Users', [
            'email' => 'foo@example.com',
            'password' => 'foobarbaz',
        ]);

        $data = [
            'user_id' => 2,
            'title' => 'title 00001',
            'body' => 'body_000001',
            'published' => false,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $this->Articles->save($entity);
        $this->assertFalse($result);
        $errors = $entity->getErrors();
        $expected = [
            'user_id' => ['_existsIn' => 'This value does not exist'],
        ];
        $this->assertEquals($expected, $errors, 'ユーザーIDが存在しない場合にエラーになること');

        $data = [
            'user_id' => 1,
            'title' => 'title 00001',
            'body' => 'body_000001',
            'published' => false,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $this->Articles->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(Article::class, $result, '正常に新規作成できること');

        $data = [
            'user_id' => 1,
            'title' => 'title 00001',
            'body' => 'body_000001',
            'published' => false,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $this->Articles->save($entity);
        $this->assertFalse($result);
        $errors = $entity->getErrors();
        $expected = [
            'slug' => ['_isUnique' => 'This value is already in use'],
        ];
        $this->assertEquals($expected, $errors, '同じタイトルの場合slugの重複エラーになること');

        $data = [
            'user_id' => 1,
            'title' => 'title 00002',
            'body' => 'body_000002',
            'published' => false,
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $this->Articles->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(Article::class, $result, '重複しないタイトルの場合追加できること');
    }

    public function testBuildTags()
    {
        Fabricate::create('Users', [
            'email' => 'foo@example.com',
            'password' => 'foobarbaz',
        ]);

        $data = [
            'user_id' => 1,
            'title' => 'Test title 001',
            'body' => 'Test body 001',
            'published' => false,
            'tag_string' => 'tag1, tag2',
        ];
        $entity = $this->Articles->newEntity($data);
        $result = $this->Articles->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(Article::class, $result);

        $result = $this->Articles->get(1, ['contain' => 'Tags']);
        $this->assertInstanceOf(Article::class, $result);
        $this->assertCount(2, $result->tags);
        $this->assertEquals('tag1, tag2', $result->tag_string);

        $entity = $this->Articles->patchEntity($result, ['tag_string' => 'tag1, tag2, tag3']);
        $result = $this->Articles->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(Article::class, $result);

        $result = $this->Articles->get(1, ['contain' => 'Tags']);
        $this->assertInstanceOf(Article::class, $result);
        $this->assertCount(3, $result->tags);
        $this->assertEquals('tag1, tag2, tag3', $result->tag_string);

        $entity = $this->Articles->patchEntity($result, ['tag_string' => 'tag1']);
        $result = $this->Articles->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(Article::class, $result);

        $result = $this->Articles->get(1, ['contain' => 'Tags']);
        $this->assertInstanceOf(Article::class, $result);
        $this->assertCount(1, $result->tags);
        $this->assertEquals('tag1', $result->tag_string);

        $result = $this->Articles->Tags->find()->all()->count();
        $this->assertEquals(3, $result);
    }

    public function testFindTagged()
    {
        Fabricate::create('Users', [
            'email' => 'foo@example.com',
            'password' => 'foobarbaz',
        ]);

        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 001',
            'body' => 'Test body 001',
            'published' => false,
            'tag_string' => 'tag1, tag2',
        ]);

        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 002',
            'body' => 'Test body 002',
            'published' => false,
            'tag_string' => 'tag1, tag3',
        ]);

        $result = $this->Articles->find('tagged', ['tags' => ['tag1']])->all();
        $this->assertCount(2, $result);

        $result = $this->Articles->find('tagged', ['tags' => ['tag3']])->all();
        $this->assertCount(1, $result);
    }
}
