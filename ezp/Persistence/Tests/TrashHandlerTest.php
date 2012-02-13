<?php
/**
 * File contains: ezp\Persistence\Tests\TrashHandlerTest class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Persistence\Tests;
use ezp\Persistence\Content\Location\Trashed as TrashedValue,
    ezp\Persistence\Content\Location\CreateStruct,
    ezp\Persistence\Content\CreateStruct as ContentCreateStruct,
    ezp\Persistence\Content\Query\Criterion\ContentId,
    ezp\Persistence\Content\Field,
    ezp\Persistence\Content\FieldValue,
    ezp\Base\Exception\NotFound,
    ezp\Content\Location,
    eZ\Publish\Core\Repository\FieldType\TextLine\Value as TextLineValue;

/**
 * Test case for Location Handler using in memory storage.
 */
class TrashHandlerTest extends HandlerTest
{
    /**
     * Number of Content and Location generated for the tests.
     *
     * @var int
     */
    protected $entriesGenerated = 5;

    /**
     * @var \ezp\Persistence\Content\Location[]
     */
    protected $locations;

    /**
     * @var \ezp\Persistence\Content[]
     */
    protected $contents;

    /**
     * Last inserted location id in setUp
     *
     * @var int
     */
    protected $lastLocationId;

    /**
     * Last inserted content id in setUp
     *
     * @var int
     */
    protected $lastContentId;

    /**
     * Locations which should be removed in tearDown
     *
     * @var \ezp\Content\Location[]
     */
    protected $locationToDelete = array();

    /**
     * Contents which should be removed in tearDown
     *
     * @var \ezp\Content[]
     */
    protected $contentToDelete = array();

    /**
     * @var \ezp\Persistence\Content\Location\Trash\Handler
     */
    protected $trashHandler;

    /**
     * Setup the HandlerTest.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->trashHandler = $this->persistenceHandler->trashHandler();
        $this->lastLocationId = 2;
        for ( $i = 0 ; $i < $this->entriesGenerated; ++$i )
        {
            $this->contents[] = $content = $this->persistenceHandler->contentHandler()->create(
                new ContentCreateStruct(
                    array(
                        "name" => array( "eng-GB" => "test_$i" ),
                        "ownerId" => 14,
                        "sectionId" => 1,
                        "typeId" => 2,
                        "fields" => array(
                            new Field(
                                array(
                                    "type" => "ezstring",
                                    // FieldValue object compatible with ezstring
                                    "value" => new FieldValue(
                                        array(
                                            "data" => new TextLineValue( "Welcome $i" )
                                        )
                                    ),
                                    "language" => "eng-GB",
                                )
                            )
                        )
                    )
                )
            );

            $this->lastContentId = $content->id;

            $this->locations[] = $location = $this->persistenceHandler->locationHandler()->create(
                new CreateStruct(
                    array(
                        "contentId" => $this->lastContentId,
                        "contentVersion" => 1,
                        "mainLocationId" => $this->lastLocationId,
                        "sortField" => Location::SORT_FIELD_NAME,
                        "sortOrder" => Location::SORT_ORDER_ASC,
                        "parentId" => $this->lastLocationId,
                    )
                )
            );

            $this->lastLocationId = $location->id;
        }

        $this->locationToDelete = $this->locations;
        $this->contentToDelete = $this->contents;
    }

    /**
     * Removes stuff created in setUp().
     */
    protected function tearDown()
    {
        $this->trashHandler->emptyTrash();
        $this->trashHandler = null;
        $locationHandler = $this->persistenceHandler->locationHandler();

        // Removing default objects as well as those created by tests
        foreach ( $this->locationToDelete as $location )
        {
            try
            {
                $locationHandler->removeSubtree( $location->id );
            }
            catch ( NotFound $e )
            {
            }
        }

        $contentHandler = $this->persistenceHandler->contentHandler();
        foreach ( $this->contentToDelete as $content )
        {
            try
            {
                $contentHandler->delete( $content->id );
            }
            catch ( NotFound $e )
            {
            }
        }

        unset( $this->lastLocationId, $this->lastContentId );
        parent::tearDown();
    }

    /**
     * Test load function
     *
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::load
     * @group trashHandler
     */
    public function testLoad()
    {
        $trashed = $this->trashHandler->trashSubtree( $this->locations[0]->id );
        $trashedId = $trashed->id;
        unset( $trashed );

        $trashed = $this->trashHandler->load( $trashedId );
        self::assertInstanceOf( 'ezp\\Persistence\\Content\\Location\\Trashed', $trashed );
        foreach ( $this->locations[0] as $property => $value )
        {
            self::assertEquals( $value, $trashed->$property, "Property {$property} did not match");
        }
    }

    /**
     * @expectedException \ezp\Base\Exception\NotFound
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::load
     * @group trashHandler
     */
    public function testLoadNonExistent()
    {
        $this->trashHandler->load( 0 );
    }

    /**
     * @group trashHandler
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::trashSubtree
     */
    public function testTrashSubtree()
    {
        $this->markTestIncomplete();
    }

    /**
     * @group trashHandler
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::untrashLocation
     */
    public function testUntrashLocation()
    {
        $this->markTestIncomplete();
    }

    /**
     * @group trashHandler
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::listTrashed
     */
    public function testListTrashed()
    {
        $this->markTestIncomplete();
    }

    /**
     * @group trashHandler
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::emptyTrash
     */
    public function testEmptyTrash()
    {
        $this->markTestIncomplete();
    }

    /**
     * @group trashHandler
     * @covers \ezp\Persistence\Storage\InMemory\TrashHandler::emptyOne
     */
    public function testEmptyOne()
    {
        $this->markTestIncomplete();
    }
}
