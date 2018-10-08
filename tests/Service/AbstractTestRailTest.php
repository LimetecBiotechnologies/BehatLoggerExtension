<?php

use PHPUnit\Framework\TestCase;
use seretos\BehatLoggerExtension\Service\AbstractTestRail;
use seretos\testrail\api\Cases;
use seretos\testrail\api\Fields;
use seretos\testrail\api\Priorities;
use seretos\testrail\api\Projects;
use seretos\testrail\api\Sections;
use seretos\testrail\api\Suites;
use seretos\testrail\api\Types;
use seretos\testrail\Client;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.10.18
 * Time: 13:34
 */

class AbstractTestRailTest extends TestCase
{
    /**
     * @var MyTestRail
     */
    private $abstractTestRail;
    /**
     * @var Client|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockClient;
    /**
     * @var Projects|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockProjects;
    /**
     * @var Suites|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSuites;
    /**
     * @var Sections|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSections;
    /**
     * @var Fields|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFields;
    /**
     * @var Priorities|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPriorities;
    /**
     * @var Cases|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockCases;
    /**
     * @var Types|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockTypes;
    /**
     * @var ReflectionClass
     */
    private $reflectionClass;

    /**
     * @throws ReflectionException
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockClient = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $this->mockProjects = $this->getMockBuilder(Projects::class)->disableOriginalConstructor()->getMock();
        $this->mockSuites = $this->getMockBuilder(Suites::class)->disableOriginalConstructor()->getMock();
        $this->mockSections = $this->getMockBuilder(Sections::class)->disableOriginalConstructor()->getMock();
        $this->mockFields = $this->getMockBuilder(Fields::class)->disableOriginalConstructor()->getMock();
        $this->mockPriorities = $this->getMockBuilder(Priorities::class)->disableOriginalConstructor()->getMock();
        $this->mockTypes = $this->getMockBuilder(Types::class)->disableOriginalConstructor()->getMock();

        $customFieldConfig = [];
        $priorityConfig = [];

        $this->mockClient->expects($this->once())->method('projects')->will($this->returnValue($this->mockProjects));
        $this->mockClient->expects($this->once())->method('suites')->will($this->returnValue($this->mockSuites));
        $this->mockClient->expects($this->once())->method('sections')->will($this->returnValue($this->mockSections));
        $this->mockClient->expects($this->once())->method('fields')->will($this->returnValue($this->mockFields));
        $this->mockClient->expects($this->once())->method('priorities')->will($this->returnValue($this->mockPriorities));
        $this->mockClient->expects($this->once())->method('cases')->will($this->returnValue($this->mockCases));
        $this->mockClient->expects($this->once())->method('types')->will($this->returnValue($this->mockTypes));

        $this->mockProjects->expects($this->once())->method('findByName')->with('myProject')->will($this->returnValue([]));
        $this->mockProjects->expects($this->once())->method('create')->with('myProject',null,false,Projects::MULTI_SUITE_MODE)->will($this->returnValue(['id' => 1]));

        $this->mockSuites->expects($this->once())->method('findByName')->with(1,'mySuite')->will($this->returnValue([]));
        $this->mockSuites->expects($this->once())->method('create')->with(1,'mySuite',null)->will($this->returnValue(['id' => 2]));

        $this->abstractTestRail = new MyTestRail($this->mockClient,'myProject','mySuite',$customFieldConfig,$priorityConfig,'custom_identifier');
        $this->reflectionClass = new ReflectionClass($this->abstractTestRail);
    }

    /**
     * @test
     */
    public function getCaseSection(){
        $method = $this->reflectionClass->getMethod('getCaseSection');
        $method->setAccessible(true);

        $this->mockSections->expects($this->at(0))->method('findByNameAndParent')->with(1,2,'mySection1',null)->will($this->returnValue(['id' => 3]));
        $this->mockSections->expects($this->at(1))->method('findByNameAndParent')->with(1,2,'mySection2',3)->will($this->returnValue([]));
        $this->mockSections->expects($this->at(2))->method('create')->with(1,2,'mySection2',null,3)->will($this->returnValue(['id' => 4]));

        $this->assertSame(4,$method->invoke($this->abstractTestRail,'mySection1=>mySection2'));
    }
}

class MyTestRail extends AbstractTestRail{

}