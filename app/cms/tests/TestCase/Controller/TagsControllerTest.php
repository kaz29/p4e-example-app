<?php
namespace App\Test\TestCase\Controller;

use App\Controller\TagsController;
use App\Model\Table\TagsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeFabricate\Adaptor\CakeFabricateAdaptor;
use Fabricate\Fabricate;
use Fabricate\FabricateContext;

/**
 * App\Controller\TagsController Test Case
 *
 * @uses \App\Controller\TagsController
 */
class TagsControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
        $config = TableRegistry::getTableLocator()->exists('Tags') ? [] : ['className' => TagsTable::class];
        $this->Tags = TableRegistry::getTableLocator()->get('Tags', $config);

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
        unset($this->Tags);

        parent::tearDown();
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        Fabricate::create('Tags', 30, function ($data, FabricateContext $world) {
            $title = function ($i) {
                return sprintf("Test title %03d", $i);
            };

            return [
                'title' => $world->sequence('title', 1, $title),
            ];
        });

        $this->get('/tags');
        $this->assertResponseCode(200);
        for ($i=1; $i<=20; $i++) {
            $this->assertResponseContains(sprintf("Test title %03d", $i));
        }

        $this->get('/tags?page=2');
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
        Fabricate::create('Tags', [
            'title' => 'Test title 001',
        ]);

        $this->get('/tags/view/1');
        $this->assertResponseCode(200);
        $this->assertResponseContains('Test title 001');

        $this->get('/tags/view/2');
        $this->assertResponseCode(404);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        $data = [
            'title' => 'Test title 001',
        ];

        $this->enableCsrfToken();
        $this->get('/tags/add');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->enableCsrfToken();
        $this->post('/tags/add', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->post('/tags/add', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/tags');

        $result = $this->Tags->get(1);
        $this->assertEquals('Test title 001', $result->title);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        Fabricate::create('Tags', [
            'title' => 'Test title 001',
        ]);

        $data = [
            'title' => 'Test title 001update',
        ];

        $this->enableCsrfToken();
        $this->get('/tags/edit/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->enableCsrfToken();
        $this->put('/tags/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->put('/tags/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/tags');

        $result = $this->Tags->get(1);
        $this->assertEquals('Test title 001update', $result->title);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        Fabricate::create('Tags', [
            'title' => 'Test title 001',
        ]);

        $this->enableCsrfToken();
        $this->post('/tags/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->post('/tags/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirect('/tags');

        $this->expectException(RecordNotFoundException::class);
        $this->Tags->get(1);
    }
}
