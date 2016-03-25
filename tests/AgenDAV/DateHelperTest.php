<?php
namespace AgenDAV;

class DateHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * UTC timezone 
     * 
     * @var mixed
     * @access private
     */
    private $utc;

    public function __construct()
    {
        $this->utc = new \DateTimeZone('UTC');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateDateTimeFail()
    {
        $dt = DateHelper::createDateTime('m/d/Y H:i', '99/99/99 99:99', $this->utc);
    }

    public function testCreateDateTimeSampleZero()
    {
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/7/2012 10:00', $this->utc);
        $dt2 = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $this->utc);

        $this->assertEquals($dt, $dt2);
    }

    public function testCreateDateTimeTZ()
    {
        $different_tz = new \DateTimeZone('Europe/Madrid');
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $different_tz);

        $this->assertEquals($dt->getTimeZone(), $different_tz);
    }

    public function testFrontendToDatetimeExample()
    {
        $str = '2014-12-15T19:45:00.000Z';

        $dt = DateHelper::frontendToDateTime($str, $this->utc);
        $this->assertEquals('201412151945', $dt->format('YmdHi'));

        // No timezone specified. Should use UTC
        $dt = DateHelper::frontendToDateTime($str);
        $this->assertEquals('201412151945', $dt->format('YmdHi'));

        $dt = DateHelper::frontendToDateTime($str, new \DateTimeZone('Europe/Madrid'));
        $this->assertEquals('201412152045', $dt->format('YmdHi'));
    }


    public function testFullcalendarToDateTime()
    {
        $str = '2012-10-07';
        $dt = DateHelper::fullcalendarToDateTime($str, $this->utc);

        $expected = new \DateTime('2012-10-07 00:00:00', $this->utc);

        $this->assertEquals($expected, $dt);
    }

    public function testAddMinutesTo()
    {
        $datetime_1 = new \DateTime('now', new \DateTimeZone('Europe/Madrid'));
        $datetime_2 = clone $datetime_1;

        $expected_1 = clone $datetime_1;
        $expected_1->modify('+10 minutes');
        $expected_2 = clone $datetime_2;
        $expected_2->modify('-10 minutes');

        DateHelper::addMinutesTo($datetime_1, '10');
        DateHelper::addMinutesTo($datetime_2, '-10');

        $this->assertEquals($expected_1, $datetime_1);
        $this->assertEquals($expected_2, $datetime_2);
    }

    public function testSwitchTimeZone()
    {
        $datetime = new \DateTime('2015-01-13 00:00:00', new \DateTimeZone('UTC'));

        $converted = DateHelper::switchTimeZone(
            $datetime,
            new \DateTimeZone('America/New_York')
        );

        $this->assertEquals('2015-01-13 00:00:00', $converted->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $converted->getTimeZone()->getName());
    }

    public function testGetStartOfDayUTC()
    {
        $datetime = new \DateTime('2015-01-27 12:03:19', new \DateTimeZone('Europe/London'));

        $start_of_day = DateHelper::getStartOfDayUTC($datetime);

        $this->assertEquals('2015-01-27 00:00:00', $start_of_day->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $start_of_day->getTimeZone()->getName());
    }
}
