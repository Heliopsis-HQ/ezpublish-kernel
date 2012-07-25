<?php
/**
 * File containing the Parser class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Input\Parser;
use eZ\Publish\Core\REST\Common\Input\ParsingDispatcher;
use eZ\Publish\Core\REST\Common\UrlHandler;
use eZ\Publish\Core\REST\Common\Exceptions;
use eZ\Publish\API\Repository\LocationService;

/**
 * Base class for input parser
 */
class LocationCreate extends Base
{
    /**
     * Location service
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Construct from location service
     *
     * @param \eZ\Publish\Core\REST\Common\UrlHandler $urlHandler
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     */
    public function __construct( UrlHandler $urlHandler, LocationService $locationService )
    {
        parent::__construct( $urlHandler );
        $this->locationService = $locationService;
    }

    /**
     * Parse input structure
     *
     * @param array $data
     * @param \eZ\Publish\Core\REST\Common\Input\ParsingDispatcher $parsingDispatcher
     * @return \eZ\Publish\API\Repository\Values\Content\LocationCreateStruct
     */
    public function parse( array $data, ParsingDispatcher $parsingDispatcher )
    {
        if ( !array_key_exists( 'ParentLocation', $data ) || !is_array( $data['ParentLocation'] ) )
        {
            throw new Exceptions\Parser( "Missing or invalid 'ParentLocation' element for LocationCreate." );
        }

        if ( !array_key_exists( '_href', $data['ParentLocation'] ) )
        {
            throw new Exceptions\Parser( "Missing '_href' attribute for ParentLocation element in LocationCreate." );
        }

        if ( !array_key_exists( 'priority', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'priority' element for LocationCreate." );
        }

        if ( !array_key_exists( 'hidden', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'hidden' element for LocationCreate." );
        }

        if ( !array_key_exists( 'sortField', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'sortField' element for LocationCreate." );
        }

        if ( !array_key_exists( 'sortOrder', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'sortOrder' element for LocationCreate." );
        }

        $locationHref = $this->urlHandler->parse( 'location', $data['ParentLocation']['_href'] );
        $locationHref = explode( '/', $locationHref['location'] );
        $locationCreateStruct = $this->locationService->newLocationCreateStruct(
            implode( '', array_slice( $locationHref, count( $locationHref ) - 1 ) )
        );

        $locationCreateStruct->priority = (int) $data['priority'];
        $locationCreateStruct->hidden = $data['hidden'] === 'true' ? true : false;
        $locationCreateStruct->sortField = constant( '\\eZ\\Publish\\API\\Repository\\Values\\Content\\Location::SORT_FIELD_' . $data['sortField'] );
        $locationCreateStruct->sortOrder = constant( '\\eZ\\Publish\\API\\Repository\\Values\\Content\\Location::SORT_ORDER_' . $data['sortOrder'] );

        return $locationCreateStruct;
    }
}

