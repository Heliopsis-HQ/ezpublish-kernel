<?php
/**
 * File containing a Test Case for LimitationType class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Limitation\Tests;

use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\LocationCreateStruct;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\User\Limitation;
use eZ\Publish\API\Repository\Values\User\Limitation\LocationLimitation;
use eZ\Publish\API\Repository\Values\User\Limitation\ObjectStateLimitation;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Limitation\LocationLimitationType;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\ContentCreateStruct;

/**
 * Test Case for LimitationType
 */
class LocationLimitationTypeTest extends Base
{
    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Location\Handler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $locationHandlerMock;

    /**
     * Setup Location Handler mock
     */
    public function setUp()
    {
        parent::setUp();

        $this->locationHandlerMock = $this->getMock(
            "eZ\\Publish\\SPI\\Persistence\\Content\\Location\\Handler",
            array(),
            array(),
            '',
            false
        );
    }

    /**
     * Tear down Location Handler mock
     */
    public function tearDown()
    {
        unset( $this->locationHandlerMock );
        parent::tearDown();
    }

    /**
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::__construct
     *
     * @return \eZ\Publish\Core\Limitation\LocationLimitationType
     */
    public function testConstruct()
    {
        return new LocationLimitationType( $this->getPersistenceMock() );
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValue()
    {
        return array(
            array( new LocationLimitation() ),
            array( new LocationLimitation( array() ) ),
            array( new LocationLimitation( array( 'limitationValues' => array( 0, PHP_INT_MAX, '2', 's3fdaf32r' ) ) ) ),
        );
    }

    /**
     * @dataProvider providerForTestAcceptValue
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::acceptValue
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation\LocationLimitation $limitation
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testAcceptValue( LocationLimitation $limitation, LocationLimitationType $locationLimitationType )
    {
        $locationLimitationType->acceptValue( $limitation );
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValueException()
    {
        return array(
            array( new ObjectStateLimitation() ),
            array( new LocationLimitation( array( 'limitationValues' => array( true ) ) ) ),
        );
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::acceptValue
     * @expectedException \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $limitation
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testAcceptValueException( Limitation $limitation, LocationLimitationType $locationLimitationType )
    {
        $locationLimitationType->acceptValue( $limitation );
    }

    /**
     * @return array
     */
    public function providerForTestValidatePass()
    {
        return array(
            array( new LocationLimitation() ),
            array( new LocationLimitation( array() ) ),
            array( new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ) ),
        );
    }

    /**
     * @dataProvider providerForTestValidatePass
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::validate
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation\LocationLimitation $limitation
     */
    public function testValidatePass( LocationLimitation $limitation )
    {
        if ( !empty( $limitation->limitationValues ) )
        {
            $this->getPersistenceMock()
                ->expects( $this->any() )
                ->method( "locationHandler" )
                ->will( $this->returnValue( $this->locationHandlerMock ) );

            foreach ( $limitation->limitationValues as $key => $value )
            {
                $this->locationHandlerMock
                    ->expects( $this->at( $key ) )
                    ->method( "load" )
                    ->with( $value );
            }
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $locationLimitationType = $this->testConstruct();

        $validationErrors = $locationLimitationType->validate( $limitation );
        self::assertEmpty( $validationErrors );
    }

    /**
     * @return array
     */
    public function providerForTestValidateError()
    {
        return array(
            array( new LocationLimitation(), 0 ),
            array( new LocationLimitation( array( 'limitationValues' => array( 0 ) ) ), 1 ),
            array( new LocationLimitation( array( 'limitationValues' => array( 0, PHP_INT_MAX ) ) ), 2 ),
        );
    }

    /**
     * @dataProvider providerForTestValidateError
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::validate
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation\LocationLimitation $limitation
     * @param int $errorCount
     */
    public function testValidateError( LocationLimitation $limitation, $errorCount )
    {
        if ( !empty( $limitation->limitationValues ) )
        {
            $this->getPersistenceMock()
                ->expects( $this->any() )
                ->method( "locationHandler" )
                ->will( $this->returnValue( $this->locationHandlerMock ) );

            foreach ( $limitation->limitationValues as $key => $value )
            {
                $this->locationHandlerMock
                    ->expects( $this->at( $key ) )
                    ->method( "load" )
                    ->with( $value )
                    ->will( $this->throwException( new NotFoundException( 'location', $value ) ) );
            }
        }
        else
        {
            $this->getPersistenceMock()
                ->expects( $this->never() )
                ->method( $this->anything() );
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $locationLimitationType = $this->testConstruct();

        $validationErrors = $locationLimitationType->validate( $limitation );
        self::assertCount( $errorCount, $validationErrors );
    }

    /**
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::buildValue
     *
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testBuildValue( LocationLimitationType $locationLimitationType )
    {
        $expected = array( 'test', 'test' => 9 );
        $value = $locationLimitationType->buildValue( $expected );

        self::assertInstanceOf( '\eZ\Publish\API\Repository\Values\User\Limitation\LocationLimitation', $value );
        self::assertInternalType( 'array', $value->limitationValues );
        self::assertEquals( $expected, $value->limitationValues );
    }

    /**
     * @return array
     */
    public function providerForTestEvaluate()
    {
        // Mocks for testing Content & VersionInfo objects, should only be used once because of expect rules.
        $contentMock = $this->getMock(
            "eZ\\Publish\\API\\Repository\\Values\\Content\\Content",
            array(),
            array(),
            '',
            false
        );

        $versionInfoMock = $this->getMock(
            "eZ\\Publish\\API\\Repository\\Values\\Content\\VersionInfo",
            array(),
            array(),
            '',
            false
        );

        $contentMock
            ->expects( $this->once() )
            ->method( 'getVersionInfo' )
            ->will( $this->returnValue( $versionInfoMock ) );

        $versionInfoMock
            ->expects( $this->once() )
            ->method( 'getContentInfo' )
            ->will( $this->returnValue( new ContentInfo() ) );

        $versionInfoMock2 = $this->getMock(
            "eZ\\Publish\\API\\Repository\\Values\\Content\\VersionInfo",
            array(),
            array(),
            '',
            false
        );

        $versionInfoMock2
            ->expects( $this->once() )
            ->method( 'getContentInfo' )
            ->will( $this->returnValue( new ContentInfo() ) );

        return array(
            // ContentInfo, with targets, no access
            array(
                'limitation' => new LocationLimitation(),
                'object' => new ContentInfo(),
                'targets' => array( new Location() ),
                'persistence' => array(),
                'expected' => false
            ),
            // ContentInfo, with targets, no access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ),
                'object' => new ContentInfo(),
                'targets' => array( new Location( array( 'id' => 55 ) ) ),
                'persistence' => array(),
                'expected' => false
            ),
            // ContentInfo, with targets, with access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ),
                'object' => new ContentInfo(),
                'targets' => array( new Location( array( 'id' => 2 ) ) ),
                'persistence' => array(),
                'expected' => true
            ),
            // ContentInfo, no targets, with access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ),
                'object' => new ContentInfo(),
                'targets' => array(),
                'persistence' => array( new Location( array( 'id' => 2 ) ) ),
                'expected' => true
            ),
            // ContentInfo, no targets, no access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2, 43 ) ) ),
                'object' => new ContentInfo(),
                'targets' => array(),
                'persistence' => array( new Location( array( 'id' => 55 ) ) ),
                'expected' => false
            ),
            // Content, with targets, with access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ),
                'object' => $contentMock,
                'targets' => array( new Location( array( 'id' => 2 ) ) ),
                'persistence' => array(),
                'expected' => true
            ),
            // VersionInfo, with targets, with access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ),
                'object' => $versionInfoMock2,
                'targets' => array( new Location( array( 'id' => 2 ) ) ),
                'persistence' => array(),
                'expected' => true
            ),
            // ContentCreateStruct, no targets, no access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2 ) ) ),
                'object' => new ContentCreateStruct(),
                'targets' => array(),
                'persistence' => array(),
                'expected' => false
            ),
            // ContentCreateStruct, with targets, no access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2, 43 ) ) ),
                'object' => new ContentCreateStruct(),
                'targets' => array( new LocationCreateStruct( array( 'parentLocationId' => 55 ) ) ),
                'persistence' => array(),
                'expected' => false
            ),
            // ContentCreateStruct, with targets, with access
            array(
                'limitation' => new LocationLimitation( array( 'limitationValues' => array( 2, 43 ) ) ),
                'object' => new ContentCreateStruct(),
                'targets' => array( new LocationCreateStruct( array( 'parentLocationId' => 43 ) ) ),
                'persistence' => array(),
                'expected' => true
            ),
        );
    }

    /**
     * @return array
     */
    public function providerForTestEvaluateInvalidArgument()
    {
        return array(
            // invalid limitation
            array(
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => array( new Location() ),
                'persistence' => array(),
            ),
            // invalid object
            array(
                'limitation' => new LocationLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => array( new Location() ),
                'persistence' => array(),
            ),
            // invalid target
            array(
                'limitation' => new LocationLimitation(),
                'object' => new ContentInfo(),
                'targets' => array( new ObjectStateLimitation() ),
                'persistence' => array(),
            ),
            // invalid target when using ContentCreateStruct
            array(
                'limitation' => new LocationLimitation(),
                'object' => new ContentCreateStruct(),
                'targets' => array( new Location() ),
                'persistence' => array(),
            ),
        );
    }

    /**
     * @dataProvider providerForTestEvaluateInvalidArgument
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::evaluate
     * @expectedException \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        array $targets,
        array $persistenceLocations
    )
    {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $locationLimitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects( $this->never() )
            ->method( $this->anything() );

        $persistenceMock = $this->getPersistenceMock();
        $persistenceMock
            ->expects( $this->never() )
            ->method( $this->anything() );

        $v = $locationLimitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );
        var_dump( $v );
    }

    /**
     * @dataProvider providerForTestEvaluate
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::evaluate
     */
    public function testEvaluate(
        LocationLimitation $limitation,
        ValueObject $object,
        array $targets,
        array $persistenceLocations,
        $expected
    )
    {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $locationLimitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects( $this->never() )
            ->method( $this->anything() );

        $persistenceMock = $this->getPersistenceMock();
        if ( empty( $persistenceLocations ) )
        {
            $persistenceMock
                ->expects( $this->never() )
                ->method( $this->anything() );
        }
        else
        {
            $this->getPersistenceMock()
                ->expects( $this->once() )
                ->method( "locationHandler" )
                ->will( $this->returnValue( $this->locationHandlerMock ) );

            $this->locationHandlerMock
                ->expects( $this->once() )
                ->method( "loadLocationsByContent" )
                ->with( $object->id )
                ->will( $this->returnValue( $persistenceLocations ) );
        }

        $value = $locationLimitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );

        self::assertInternalType( 'boolean', $value );
        self::assertEquals( $expected, $value );
    }

    /**
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::getCriterion
     * @expectedException \RuntimeException
     *
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testGetCriterionInvalidValue( LocationLimitationType $locationLimitationType )
    {
        $locationLimitationType->getCriterion(
            new LocationLimitation( array() ),
            $this->getUserMock()
        );
    }

    /**
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::getCriterion
     *
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testGetCriterionSingleValue( LocationLimitationType $locationLimitationType )
    {
        $criterion = $locationLimitationType->getCriterion(
            new LocationLimitation( array( 'limitationValues' => array( 9 ) ) ),
            $this->getUserMock()
        );

        self::assertInstanceOf( '\eZ\Publish\API\Repository\Values\Content\Query\Criterion\LocationId', $criterion );
        self::assertInternalType( 'array', $criterion->value );
        self::assertInternalType( 'string', $criterion->operator );
        self::assertEquals( Operator::EQ, $criterion->operator );
        self::assertEquals( array( 9 ), $criterion->value );
    }

    /**
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::getCriterion
     *
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testGetCriterionMultipleValues( LocationLimitationType $locationLimitationType )
    {
        $criterion = $locationLimitationType->getCriterion(
            new LocationLimitation( array( 'limitationValues' => array( 9, 55 ) ) ),
            $this->getUserMock()
        );

        self::assertInstanceOf( '\eZ\Publish\API\Repository\Values\Content\Query\Criterion\LocationId', $criterion );
        self::assertInternalType( 'array', $criterion->value );
        self::assertInternalType( 'string', $criterion->operator );
        self::assertEquals( Operator::IN, $criterion->operator );
        self::assertEquals( array( 9, 55 ), $criterion->value );
    }

    /**
     * @depends testConstruct
     * @covers \eZ\Publish\Core\Limitation\LocationLimitationType::valueSchema
     *
     * @param \eZ\Publish\Core\Limitation\LocationLimitationType $locationLimitationType
     */
    public function testValueSchema( LocationLimitationType $locationLimitationType )
    {
        self::assertEquals(
            LocationLimitationType::VALUE_SCHEMA_LOCATION_ID,
            $locationLimitationType->valueSchema()
        );
    }
}
