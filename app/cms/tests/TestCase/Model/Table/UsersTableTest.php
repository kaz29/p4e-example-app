<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeFabricate\Adaptor\CakeFabricateAdaptor;
use Fabricate\Fabricate;

/**
 * App\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
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
        'app.Articles'
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
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->assertInstanceOf(UsersTable::class, $this->Users);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $data = [];
        $entity = $this->Users->newEntity($data);
        $errors = $entity->getErrors();
        $expected = [
            'email' => ['_required' => 'This field is required'],
            'password' => ['_required' => 'This field is required'],
        ];
        $this->assertEquals($expected, $errors);

        $data = [
            'email' => 'foobar',
            'password' => str_repeat('a', 256),
        ];
        $entity = $this->Users->newEntity($data);
        $errors = $entity->getErrors();
        $expected = [
            'email' => ['email' => 'The provided value is invalid'],
            'password' => ['maxLength' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);

        $data = [
            'email' => 'foo@example.com',
            'password' => str_repeat('a', 7),
        ];
        $entity = $this->Users->newEntity($data);
        $errors = $entity->getErrors();
        $expected = [
            'password' => ['minLength' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);

        $data = [
            'email' => 'foo@example.com',
            'password' => str_repeat('a', 8),
        ];
        $entity = $this->Users->newEntity($data);
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
        Fabricate::create('Users', [
            'email' => 'foo@example.com',
            'password' => 'foobarbaz',
        ]);

        $data = [
            'email' => 'foo@example.com',
            'password' => str_repeat('a', 8),
        ];
        $entity = $this->Users->newEntity($data);
        $result = $this->Users->save($entity);
        $this->assertFalse($result);
        $errors = $entity->getErrors();
        $expected = [
            'email' => ['_isUnique' => 'This value is already in use'],
        ];
        $this->assertEquals($expected, $errors, 'メールアドレスが重複する場合エラーになること');

        $password = str_repeat('a', 8);
        $data = [
            'email' => 'bar@example.com',
            'password' => $password,
        ];
        $entity = $this->Users->newEntity($data);
        $result = $this->Users->save($entity);
        $this->assertNotFalse($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertNotEquals($password, $result->password, 'パスワードがそのまま保存されていないこと');

        $this->assertTrue((new DefaultPasswordHasher())->check($password, $result->password), 'ハッシュ化されたパスワードのチェックが正常に動作すること');
    }
}
