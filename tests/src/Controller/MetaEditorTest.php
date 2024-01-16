<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\metaedit\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\metaedit\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "metaedit" module.
 *
 * @covers \SimpleSAML\Module\metaedit\Controller\MetaEditor
 */
class MetaEditorTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['metaedit' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'useridattr' => 'uid',
                    'metahandlerConfig' => ['directory' => '/tmp'],
                    'auth' => 'phpunit',
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'module_metaedit.php',
            'simplesaml'
        );

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that accessing the main-endpoint results in a Template being returned
     *
     * @return void
     */
    public function testMain(): void
    {
        $request = Request::create(
            '/',
            'GET'
        );

        $c = new Controller\MetaEditor($this->config, $this->session);
        $c->setAuthSimple(new class ('admin') extends Auth\Simple {
            public function login(array $params = []): void
            {
                // stub
            }
            public function getAttributes(): array
            {
                return ['uid' => 'test'];
            }
        });

        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the edit-endpoint results in a Template being returned
     *
     * @return void
     */
    public function testEdit(): void
    {
        $request = Request::create(
            '/edit',
            'GET'
        );

        $c = new Controller\MetaEditor($this->config, $this->session);
        $c->setAuthSimple(new class ('admin') extends Auth\Simple {
            public function login(array $params = []): void
            {
                // stub
            }
            public function getAttributes(): array
            {
                return ['uid' => 'test'];
            }
        });

        $response = $c->edit($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the import-endpoint results in a Template being returned
     *
     * @return void
     */
    public function testImport(): void
    {
        $c = new Controller\MetaEditor($this->config, $this->session);
        $response = $c->import();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that a missing SourceID results in an error-response
     *
     * @dataProvider endpoints
     * @param string $endpoint
     * @param string $method
     * @return void
     */
    public function testMissingUserId(string $endpoint, string $method): void
    {
        $request = Request::create(
            '/' . $endpoint,
            'GET'
        );

        $c = new Controller\MetaEditor($this->config, $this->session);
        $c->setAuthSimple(new class ('admin') extends Auth\Simple {
            public function login(array $params = []): void
            {
                // stub
            }
        });

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('User ID is missing');
        call_user_func([$c, $method], $request);
    }


    /**
     * @return array
     */
    public static function endpoints(): array
    {
        return [
            ['', 'main'],
            ['edit', 'edit'],
        ];
    }
}
