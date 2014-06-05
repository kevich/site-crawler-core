<?php


class CrawlerTest extends \PHPUnit_Framework_TestCase
{

    public function testCanGetInstanceOfCrawler()
    {
        $this->assertInstanceOf('\Crawler', self::crawler());
    }

    /**
     * @runInSeparateProcess
     */
    public function testReturnsObjectWithItemSourceInterface()
    {

        $parserMock = Mockery::mock('overload:\\Crawler\\Parser\\TestParser', '\\Crawler\\Parser\\ParserInterface');
        $parserMock->shouldReceive('getItemSourceClassName')->andReturn('TestItemSource')->once();

        $itemSourceStub = Mockery::mock('alias:TestItemSource', '\\Crawler\\ItemSource\\ItemSourceInterface');

        $runner = self::runnerStub();
        $runner->shouldReceive('proceed')->andReturn($itemSourceStub)->once();

        $this->assertInstanceOf(
            '\Crawler\ItemSource\ItemSourceInterface',
            self::crawler()->crawlItems(
                'http://url.ch',
                'TestParser',
                'RunnerCurl',
                null,
                $runner
            )
        );

        $this->assertEquals(
            $itemSourceStub,
            self::crawler()->crawlItems(
                'http://url.ch',
                'TestParser',
                'RunnerCurl',
                null,
                $runner
            )
        );

    }

    public function testReturnNullIfParserTypeNotExist()
    {
        $this->assertNull(self::crawler()->crawlItems('url', 'NotExistParser', 'RunnerCurl'));
    }

//----------------------------------------------------------------------------------------------------------------------

    private static function crawler($config = array())
    {
        return new Crawler($config);
    }

    private static function runnerStub()
    {
        return Mockery::mock('\\Crawler\\RunnerInterface',
            function ($mock) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldIgnoreMissing();
            }
        );
    }
}
 