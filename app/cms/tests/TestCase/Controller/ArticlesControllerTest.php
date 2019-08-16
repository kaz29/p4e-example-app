<?php
namespace App\Test\TestCase\Controller;

use App\Controller\ArticlesController;
use App\Model\Table\ArticlesTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeFabricate\Adaptor\CakeFabricateAdaptor;
use Fabricate\Fabricate;
use Fabricate\FabricateContext;

/**
 * App\Controller\ArticlesController Test Case
 *
 * @uses \App\Controller\ArticlesController
 */
class ArticlesControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
        'app.ArticlesTags'
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

        Fabricate::create('Users', [
            'email' => 'foo@example.com',
            'password' => 'foobarbaz',
        ]);
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
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        Fabricate::create('Articles', 30, function ($data, FabricateContext $world) {
            $title = function ($i) {
                return sprintf("Test title %03d", $i);
            };

            return [
                'user_id' => 1,
                'title' => $world->sequence('title', 1, $title),
                'slug' => null,
                'published' => false,
            ];
        });

        $this->get('/articles');
        $this->assertResponseCode(200);
        for ($i=1; $i<=20; $i++) {
            $this->assertResponseContains(sprintf("Test title %03d", $i));
        }

        $this->get('/articles?page=2');
        $this->assertResponseCode(200);
        for ($i=21; $i<=30; $i++) {
            $this->assertResponseContains(sprintf("Test title %03d", $i));
        }
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 001',
            'body' => 'TEST001BODY',
            'slug' => null,
            'published' => false,
        ]);

        $this->get('/articles/view/Test-title-001');
        $this->assertResponseCode(200);
        $this->assertResponseContains('Test title 001');
        $this->assertResponseContains('TEST001BODY');
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        $this->get('/articles/add');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $data = [
            'title' => 'Test title 001',
            'body' => 'TEST001BODY',
            'published' => false,
            'tag_string' => 'tag1, tag2'
        ];

        $this->enableCsrfToken();
        $this->post('/articles/add');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->get('/articles/add');
        $this->assertResponseCode(200);

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->post('/articles/add', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/articles');

        $result = $this->Articles->get(1, ['contain' => 'Tags']);
        $this->assertEquals('Test-title-001', $result->slug);
        $this->assertCount(2, $result->tags);
        $this->assertEquals('tag1, tag2', $result->tag_string);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $this->get('/articles/edit/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 001',
            'body' => 'TEST001BODY',
            'slug' => null,
            'published' => false,
        ]);

        $data = [
            'title' => 'Test title 001update',
            'body' => 'TEST001BODYupdate',
            'published' => false,
            'tag_string' => 'tag1, tag2, tag3'
        ];
        $this->enableCsrfToken();
        $this->put('/articles/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->get('/articles/edit/1');
        $this->assertResponseCode(200);

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->put('/articles/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/articles');

        $result = $this->Articles->get(1, ['contain' => 'Tags']);
        $this->assertEquals('Test title 001update', $result->title);
        $this->assertEquals('TEST001BODYupdate', $result->body);
        $this->assertEquals('Test-title-001', $result->slug);
        $this->assertCount(3, $result->tags);
        $this->assertEquals('tag1, tag2, tag3', $result->tag_string);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 001',
            'body' => 'TEST001BODY',
            'slug' => null,
            'published' => false,
        ]);

        $this->enableCsrfToken();
        $this->post('/articles/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->post('/articles/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirect('/articles');

        $this->expectException(RecordNotFoundException::class);
        $this->Articles->get(1, ['contain' => 'Tags']);
    }

    public function testTags()
    {
        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 001',
            'body' => 'TEST001BODY',
            'slug' => null,
            'published' => false,
            'tag_string' => 'tag1, tag2'
        ]);
        Fabricate::create('Articles', [
            'user_id' => 1,
            'title' => 'Test title 002',
            'body' => 'TEST002BODY',
            'slug' => null,
            'published' => false,
            'tag_string' => 'tag2, tag 3'
        ]);

        $this->get('/articles/tags/tag1');
        $this->assertResponseCode(200);
        $this->assertResponseContains('Test title 001');

        $this->get('/articles/tags/tag2');
        $this->assertResponseCode(200);
        $this->assertResponseContains('Test title 001');
        $this->assertResponseContains('Test title 002');

        $this->get('/articles/tags/tag%203');
        $this->assertResponseCode(200);
        $this->assertResponseContains('Test title 002');
    }
}
