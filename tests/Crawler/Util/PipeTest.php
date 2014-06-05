<?php

namespace Crawler\Util;

use \Mockery as m;

class PipeTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testCanReadWithMarkerForEmptyData()
    {
        $pipe = $this->pipe();
        $pipe->shouldReceive('streamGetContents')->andReturn('');

        $res = $pipe->readWithMarker('#123#', 1);
        $this->assertFalse($res);
    }

    public function testCanReadWithMarker()
    {
        $pipe = $this->pipe();

        $pipe->shouldReceive('streamGetContents')->times(5)->andReturn('aaaaa', 'b#123#cc', 'ddd#1', '23#rrrrr', '');

        $res = $pipe->readWithMarker('#123#');
        $this->assertEquals('aaaaab', $res);

        $res = $pipe->readWithMarker('#123#');
        $this->assertEquals('ccddd', $res);

        $res = $pipe->readWithMarker('#123#');
        $this->assertFalse($res);
    }

    /**
     * @return m\MockInterface|\Crawler\Util\Pipe
     */
    private function pipe()
    {
        $pipe = m::mock('\Crawler\Util\Pipe[streamGetContents, getStatusRunning]');
        $pipe->shouldReceive('getStatusRunning')->andReturn(false);
        return $pipe;
    }

}

 