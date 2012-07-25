<?php
/**
 * File containing the Role controller class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Controller;
use eZ\Publish\Core\REST\Common\UrlHandler;
use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Common\Input;
use eZ\Publish\Core\REST\Server\Values;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;

use Qafoo\RMF;

/**
 * Location controller
 */
class Location
{
    /**
     * Input dispatcher
     *
     * @var \eZ\Publish\Core\REST\Common\Input\Dispatcher
     */
    protected $inputDispatcher;

    /**
     * URL handler
     *
     * @var \eZ\Publish\Core\REST\Common\UrlHandler
     */
    protected $urlHandler;

    /**
     * Location service
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Content service
     *
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * Construct controller
     *
     * @param \eZ\Publish\Core\REST\Common\Input\Dispatcher $inputDispatcher
     * @param \eZ\Publish\Core\REST\Common\UrlHandler $urlHandler
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     */
    public function __construct( Input\Dispatcher $inputDispatcher, UrlHandler $urlHandler, LocationService $locationService, ContentService $contentService )
    {
        $this->inputDispatcher = $inputDispatcher;
        $this->urlHandler      = $urlHandler;
        $this->locationService = $locationService;
        $this->contentService  = $contentService;
    }

    /**
     * Creates a new location for the given content object
     *
     * @param \Qafoo\RMF\Request $request
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function createLocation( RMF\Request $request )
    {
        $values = $this->urlHandler->parse( 'objectLocations', $request->path );

        $locationCreateStruct = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type', $request->contentType ),
                $request->body
            )
        );

        $contentInfo = $this->contentService->loadContentInfo( $values['object'] );
        return $this->locationService->createLocation( $contentInfo, $locationCreateStruct );
    }

    /**
     * Loads a location
     *
     * @param \Qafoo\RMF\Request $request
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function loadLocation( RMF\Request $request )
    {
        $values = $this->urlHandler->parse( 'location', $request->path );

        $locationId = explode( '/', $values['location'] );
        $locationId = implode( '', array_slice( $locationId, 0, count( $locationId ) - 1 ) );

        return $this->locationService->loadLocation( $locationId );
    }

    /**
     * Loads all locations for content object
     *
     * @param \Qafoo\RMF\Request $request
     * @return \eZ\Publish\API\Repository\Values\Content\Location[]
     */
    public function loadLocationsForContent( RMF\Request $request )
    {
        $values = $this->urlHandler->parse( 'objectLocations', $request->path );

        return new Values\LocationList(
            $this->locationService->loadLocations(
                $this->contentService->loadContentInfo( $values['object'] )
            ),
            $values['object']
        );
    }
}
