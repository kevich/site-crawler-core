<?php


namespace Crawler;

use \Mockery as m;

class RunnerJSTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }


    public function testCanConstructRunner()
    {
        $runner = new RunnerJS(m::mock('\Crawler\Parser\ParserInterface'));
        $this->assertInstanceOf('\Crawler\RunnerJS', $runner);
    }

    public function testCanProceed()
    {
        $pipe = $this->pipeStub();
        $pipe->shouldReceive('readWithMarker')->times(3)->andReturn('aaa', 'bbb', false);

        $res = self::runner($pipe)->proceed('http://www.google.com', self::itemSource());
        $this->assertNotEmpty($res);
        $this->assertInstanceOf('\Crawler\ItemSource\ItemSourceInterface', $res);
    }

    public function testCanProceedWithFilter()
    {
        $filter = m::mock('\Crawler\Filter\FilterInterface');
        $filter->shouldReceive('apply')->andReturn('testFilter');

        $parser = self::parser();
        $parser->shouldReceive('parseItemDetails')
               ->with('testFilter', 'test')
               ->andReturn(array())
            ->once();
        
        $pipe = $this->pipeStub();
        $pipe->shouldReceive('readWithMarker')->andReturn('test', false);
        
        $runner = self::runner($pipe, $parser);
        $runner->setFilter($filter);

        $res = $runner->proceed('http://www.google.com', self::itemSource());

        $this->assertInstanceOf('\Crawler\ItemSource\ItemSourceInterface', $res);

    }


//----------------------------------------------------------------------------------------------------------------------

    private static function runner($pipe = null, $parser = null)
    {

        /** @noinspection PhpParamsInspection */
        $runner = new RunnerJS(
            $parser ? : m::mock(
                '\Crawler\Parser\ParserInterface',
                function ($mock) {
                    /** @var $mock \Mockery\MockInterface */
                    $mock->shouldIgnoreMissing();
                }
            )
        );
        $runner->setTestPipe(
            $pipe ? : m::mock(
                '\Crawler\Util\Pipe',
                function ($mock) {
                    /** @var $mock \Mockery\MockInterface */
                    $mock->shouldIgnoreMissing();
                }
            )
        );
        return $runner;
    }

    private static function pipeStub()
    {
        return m::mock('\Crawler\Util\Pipe',
            function ($mock) {
                /** @var $mock \Mockery\MockInterface */
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

    /**
     * @return \Crawler\Parser\ParserInterface | \Mockery\MockInterface
     */
    private static function parser()
    {
        return m::mock(
            '\Crawler\Parser\ParserInterface',
            function ($mock) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldIgnoreMissing();
            }
        );
    }

}

 