<?php
namespace AgenDAV\XML;

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Share\Permissions;
use AgenDAV\CalDAV\Share\ACL;
use \Mockery as m;

/**
 * @author jorge
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testPropfindBody()
    {
        $generator = $this->createXMLGenerator();

        $body = trim($generator->propfindBody(array(
            '{DAV:}resourcetype',
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://fake.namespace.org}calendar-color'
        )));

        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<d:propfind xmlns:d="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:A="http://apple.com/ns/ical/" xmlns:x0="http://fake.namespace.org">
<d:prop>
    <d:resourcetype/>
    <C:calendar-home-set/>
    <A:calendar-color/>
    <x0:calendar-color/>
</d:prop>
</d:propfind>
EOXML;
        $this->assertXmlStringEqualsXmlString($expected, $body);
    }

    public function testMkCalendarBody()
    {
        $generator = $this->createXMLGenerator();

        $properties = [
            Calendar::DISPLAYNAME => 'Calendar name',
            '{urn:fake}attr' => 'value',
        ];

        $body = $generator->mkCalendarBody($properties);

        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<C:mkcalendar xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:x0="urn:fake">
    <d:set>
        <d:prop>
            <d:displayname>Calendar name</d:displayname>
            <x0:attr>value</x0:attr>
        </d:prop>
    </d:set>
</C:mkcalendar>
EOXML;

        $dom_expected = new \DOMDocument;
        $dom_expected->loadXML($expected);

        $dom_actual = new \DOMDocument;
        $dom_actual->loadXML($body);

        $this->assertEqualXMLStructure(
            $dom_expected->firstChild,
            $dom_actual->firstChild
        );

        //$this->assertXmlStringEqualsXmlString($expected, $body);
    }

    /**
     * Make sure that the body doesn't contain a <set><prop></prop></set> group
     */
    public function testMkCalendarBodyWithoutProperties()
    {
        $generator = $this->createXMLGenerator();

        $body = $generator->mkCalendarBody([]);

        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<C:mkcalendar xmlns:C="urn:ietf:params:xml:ns:caldav"></C:mkcalendar>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }

    public function testproppatchBody()
    {
        $generator = $this->createXMLGenerator();

        $properties = [
            Calendar::DISPLAYNAME => 'Calendar name',
            Calendar::COLOR => '#f0f0f0aa',
            '{urn:fake}attr' => 'value',
        ];

        $body = $generator->proppatchBody($properties);

        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:A="http://apple.com/ns/ical/" xmlns:x0="urn:fake">
<d:set>
    <d:prop>
        <d:displayname>Calendar name</d:displayname>
        <A:calendar-color>#f0f0f0aa</A:calendar-color>
        <x0:attr>value</x0:attr>
    </d:prop>
</d:set>
</d:propertyupdate>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }

    public function testEventsReportBody()
    {
        $generator = $this->createXMLGenerator();
        $fake_filter = m::mock('\AgenDAV\CalDAV\ComponentFilter')
            ->shouldReceive('generateFilterXML')
            ->once()
            ->andReturn(new \DOMElement('test'))
            ->getMock();

        $body = $generator->reportBody($fake_filter);

        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
    <d:prop>
        <d:getetag/>
        <C:calendar-data/>
    </d:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT">
                <test />
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }


    public function testACLGenerator()
    {
        $permissions = new permissions([
            'owner' => [ '{DAV:}all', '{urn:he:man}master-of-universe' ],
            'default' => [ '{urn:ietf:params:xml:ns:caldav}read-free-busy' ],
            'read-write' => [ '{DAV:}write' ],
            'read-only' => [ '{DAV:}read' ],
        ]);

        $acl = new ACL($permissions);
        $acl->addGrant('/jorge', 'read-write');
        $acl->addGrant('/rigodon', 'read-only');

        $generator = $this->createXMLGenerator();
        $generated_acl = $generator->aclBody($acl);

        $expected_acl = <<<ACLBODY
<?xml version="1.0" encoding="UTF-8"?>
<d:acl xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:x0="urn:he:man">
  <d:ace>
    <d:principal>
      <d:property>
        <d:owner/>
      </d:property>
    </d:principal>
    <d:grant>
      <d:all/>
      <x0:master-of-universe/>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:authenticated/>
    </d:principal>
    <d:grant>
      <C:read-free-busy/>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/jorge</d:href>
    </d:principal>
    <d:grant>
      <d:write/>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/rigodon</d:href>
    </d:principal>
    <d:grant>
      <d:read/>
    </d:grant>
  </d:ace>
</d:acl>
ACLBODY;

        $this->assertXmlStringEqualsXmlString($expected_acl, $generated_acl);

    }

    public function testPrincipalPropertySearchBody()
    {
      $filter = new \AgenDAV\CalDAV\PrincipalPropertySearchFilter('example');
      $generator = $this->createXMLGenerator();
      $body = $generator->principalPropertySearchBody($filter);
      $expected_body = <<<BODY
<d:principal-property-search xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" test="anyof">
  <d:property-search>
    <d:prop>
      <C:calendar-user-address-set/>
      <d:match>example</d:match>
    </d:prop>
  </d:property-search>
  <d:property-search>
    <d:prop>
      <d:displayname/>
      <d:match>example</d:match>
    </d:prop>
  </d:property-search>
  <d:prop>
    <d:displayname/>
    <d:email/>
  </d:prop>
</d:principal-property-search>
BODY;

      $this->assertXmlStringEqualsXmlString($expected_body, $body);
    }

    /**
     * Create a new XMLGenerator without output formatting
     **/
    public function createXMLGenerator()
    {
        return new Generator(false);
    }
}
