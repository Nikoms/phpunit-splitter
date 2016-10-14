<?php
namespace Nikoms\Tests\Functional;

class LotOfTests1Test extends \PHPUnit_Framework_TestCase
{
    public function giveMe5000()
    {
        return array_fill(0, 10000, [true]);
    }

    /**
     * @group        groupTrue1
     * @dataProvider giveMe5000
     */
    public function testTrue1($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue2
     * @dataProvider giveMe5000
     */
    public function testTrue2($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue3
     * @dataProvider giveMe5000
     */
    public function testTrue3($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue4
     * @dataProvider giveMe5000
     */
    public function testTrue4($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue5
     * @dataProvider giveMe5000
     */
    public function testTrue5($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue6
     * @dataProvider giveMe5000
     */
    public function testTrue6($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue7
     * @dataProvider giveMe5000
     */
    public function testTrue7($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }

    /**
     * @group        groupTrue8
     * @dataProvider giveMe5000
     */
    public function testTrue8($value)
    {
        for($i=0;$i<10000;$i++){}
        $this->assertTrue($value);
    }


    /**
     * @group testError
     */
    public function testError()
    {
        $this->assertFalse(true);
    }
}