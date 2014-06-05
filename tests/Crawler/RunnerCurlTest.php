<?php


namespace Crawler;

use \Mockery as m;

class RunnerCurlTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testCanConstructRunner()
    {
        $this->assertInstanceOf('\Crawler\RunnerCurl', self::runner());
    }

    public function testCanProceedWithBlankData()
    {
        $res = self::runner()->proceed('http://www.google.com', self::itemSource());
        $this->assertNotEmpty($res);
        $this->assertInstanceOf('\Crawler\ItemSource\ItemSourceInterface', $res);
    }

    public function testDoNotGetPageAgainIfPageUrlsFunctionReturnedNull()
    {
        $parser = m::mock('\Crawler\Parser\ParserInterface');
        $parser->shouldIgnoreMissing();
        $parser->shouldReceive('getPagesUrls')->andReturn(null);
        $parser->shouldReceive('getItemsUrls')->andReturn(array());

        $client = m::mock('\GuzzleHttp\Message\Request');
        $client->shouldIgnoreMissing();
        $client->shouldReceive('send')->with()->never();

        self::runner($client, $parser)->proceed('http://test.ch', self::itemSource());

    }

    public function testHandlesOnePageList()
    {
        $parser = m::mock('\Crawler\Parser\ParserInterface');
        $parser->shouldIgnoreMissing();
        $parser->shouldReceive('getPagesUrls')->andReturn(null);
        $parser->shouldReceive('getItemsUrls')->andReturn(array('http://item.url.ch'));

        $requestStub = self::requestStub();

        $client = m::mock('GuzzleHttp\Client');
        $client->shouldIgnoreMissing();
        $client->shouldReceive('get')->with('http://test.ch')->andReturn($requestStub)->once();
        $client->shouldReceive('createRequest')->with('GET', 'http://item.url.ch')->andReturn($requestStub)->once();
        $client->shouldReceive('sendAll')->with(array($requestStub), m::any())->once();

        self::runner($client, $parser)->proceed('http://test.ch', self::itemSource());

    }

//----------------------------------------------------------------------------------------------------------------------

    private static function runner($client = null, $parser = null)
    {

        /** @noinspection PhpParamsInspection */
        $runner = new RunnerCurl(
            $parser ? : m::mock(
                '\Crawler\Parser\ParserInterface',
                function ($mock) {
                    /** @var $mock \Mockery\MockInterface */
                    $mock->shouldIgnoreMissing();
                }
            )
        );

        $runner->setTestClient(
            $client ? : m::mock(
                '\Guzzle\Http\Message\Request',
                function ($mock) {
                    /** @var $mock \Mockery\MockInterface */
                    $mock->shouldIgnoreMissing();
                }
            )
        );

        return $runner;
    }

    private static function requestStub()
    {
        return m::mock(
            '\Guzzle\Http\Message\Request',
            function ($mock) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldIgnoreMissing();
            }
        );
    }

    private static function responseStub()
    {
        return m::mock(
            '\Guzzle\Http\Message\Response',
            function ($mock) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldReceive('getEffectiveUrl')->andReturn('');
                $mock->shouldIgnoreMissing();
            }
        );
    }

    /**
     * @return \Crawler\ItemSource\ItemSourceInterface
     */
    private static function itemSource()
    {
        return m::mock(
            '\Crawler\ItemSource\ItemSourceInterface',
            function ($mock) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldIgnoreMissing();
            }
        );
    }

}

 