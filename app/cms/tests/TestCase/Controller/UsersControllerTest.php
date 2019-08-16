<?php
namespace App\Test\TestCase\Controller;

use App\Controller\UsersController;
use App\Model\Table\UsersTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeFabricate\Adaptor\CakeFabricateAdaptor;
use Fabricate\Fabricate;
use Fabricate\FabricateContext;

/**
 * App\Controller\UsersController Test Case
 *
 * @uses \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersTable
     */
    public $Users;

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
        $config = TableRegistry::getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = TableRegistry::getTableLocator()->get('Users', $config);

        Fabricate::config(function ($config) {
            $config->adaptor = new CakeFabricateAdaptor([CakeFabricateAdaptor::OPTION_FILTER_KEY => true]);
        });

        Fabricate::create('Users', [
            'email' => 'foo-001@example.com',
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
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        Fabricate::create('Users', 30, function ($data, FabricateContext $world) {
            $email = function ($i) {
                return sprintf("foo-%03d@example.com", $i);
            };

            return [
                'email' => $world->sequence('email', 2, $email),
                'password' => 'foobarbaz',
            ];
        });

        $this->get('/users');
        $this->assertResponseCode(200);
        for ($i=1; $i<=20; $i++) {
            $this->assertResponseContains(sprintf("foo-%03d@example.com", $i));
        }

        $this->get('/users?page=2');
        $this->assertResponseCode(200);
        for ($i=21; $i<=30; $i++) {
            $this->assertResponseContains(sprintf("foo-%03d@example.com", $i));
        }
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        $this->get('/users/view/1');
        $this->assertResponseCode(200);
        $this->assertResponseContains('foo-001@example.com');

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
            'email' => 'foo@example.com',
            'password' => 'foobarbaz',
        ];

        $this->enableCsrfToken();
        $this->get('/users/add');
        $this->assertResponseCode(200);

        $this->enableCsrfToken();
        $this->post('/users/add', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/users');

        $result = $this->Users->get(2);
        $this->assertEquals('foo@example.com', $result->email);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $data = [
            'email' => 'foo@example.com',
        ];

        $this->enableCsrfToken();
        $this->get('/users/edit/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->enableCsrfToken();
        $this->put('/users/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->put('/users/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/users');

        $result = $this->Users->get(1);
        $this->assertEquals('foo@example.com', $result->email);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->enableCsrfToken();
        $this->post('/users/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');

        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->enableCsrfToken();
        $this->post('/users/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirect('/users');

        $this->expectException(RecordNotFoundException::class);
        $this->Users->get(1);
    }

    public function testLogin()
    {
        $this->enableCsrfToken();
        $this->get('/users/login');
        $this->assertResponseCode(200);

        $data = [
            'email' => 'foo-001@example.com',
            'password' => '_error_password_',
        ];
        $this->post('/users/login', $data);
        $this->assertResponseCode(200);
        $this->assertResponseContains('Your username or password is incorrect.');

        $data = [
            'email' => 'foo-001@example.com',
            'password' => 'foobarbaz',
        ];
        $this->post('/users/login', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
    }

    public function testLogout()
    {
        $this->enableCsrfToken();
        $this->get('/users/logout');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');
    }
}
