<?php
/**
 * File containing the eZ\Publish\API\Repository\Values\User\Limitation\ParentUserGroupLimitation class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Limitation;

use eZ\Publish\API\Repository\Exceptions\NotFoundException as APINotFoundException;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\API\Repository\Values\User\User as APIUser;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationCreateStruct;
use eZ\Publish\Core\Base\Exceptions\BadStateException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\API\Repository\Values\User\Limitation\ParentUserGroupLimitation as APIParentUserGroupLimitation;
use eZ\Publish\API\Repository\Values\User\Limitation as APILimitationValue;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\SPI\Limitation\Type as SPILimitationTypeInterface;
use eZ\Publish\Core\FieldType\ValidationError;

/**
 * ParentUserGroupLimitation is a Content limitation
 */
class ParentUserGroupLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $limitationValue
     */
    public function acceptValue( APILimitationValue $limitationValue )
    {
        if ( !$limitationValue instanceof APIParentUserGroupLimitation )
        {
            throw new InvalidArgumentType( "\$limitationValue", "APIParentUserGroupLimitation", $limitationValue );
        }
        else if ( !is_array( $limitationValue->limitationValues ) )
        {
            throw new InvalidArgumentType( "\$limitationValue->limitationValues", "array", $limitationValue->limitationValues );
        }

        foreach ( $limitationValue->limitationValues as $key => $value )
        {
            // Accept a true value for b/c with 5.0
            if ( $value === true )
            {
                $limitationValue->limitationValues[$key] = 1;
            }
            // Cast integers passed as string to int
            else if ( is_string( $value ) && $value == ((int)$value) )
            {
                $limitationValue->limitationValues[$key] = (int)$value;
            }
            else if ( !is_int( $value ) )
            {
                throw new InvalidArgumentType( "\$limitationValue->limitationValues[{$key}]", "int", $value );
            }
        }
    }

    /**
     * Makes sure LimitationValue->limitationValues is valid according to valueSchema().
     *
     * Make sure {@link acceptValue()} is checked first!
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $limitationValue
     *
     * @return \eZ\Publish\SPI\FieldType\ValidationError[]
     */
    public function validate( APILimitationValue $limitationValue )
    {
        $validationErrors = array();
        foreach ( $limitationValue->limitationValues as $key => $value )
        {
            if ( $value !== 1 )
            {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' must be 1 (owner)",
                    null,
                    array(
                        "value" => $value,
                        "key" => $key
                    )
                );
            }
        }
        return $validationErrors;
    }

    /**
     * Create the Limitation Value
     *
     * @param mixed[] $limitationValues
     *
     * @return \eZ\Publish\API\Repository\Values\User\Limitation
     */
    public function buildValue( array $limitationValues )
    {
        return new APIParentUserGroupLimitation( array( 'limitationValues' => $limitationValues ) );
    }

    /**
     * Evaluate permission against content & target(placement/parent/assignment)
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1,  2 ]
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $value
     * @param \eZ\Publish\API\Repository\Values\User\User $currentUser
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object
     * @param \eZ\Publish\API\Repository\Values\ValueObject|null $target The location, parent or "assignment" value object
     *
     * @return boolean
     */
    public function evaluate( APILimitationValue $value, APIUser $currentUser, ValueObject $object, ValueObject $target = null )
    {
        if ( !$value instanceof APIParentUserGroupLimitation )
            throw new InvalidArgumentException( '$value', 'Must be of type: APIParentUserGroupLimitation' );

        if ( $value->limitationValues[0] != 1 )
        {
            throw new BadStateException(
                'Parent User Group limitation',
                'expected limitation value to be 1 but got:' . $value->limitationValues[0]
            );
        }

        if ( $target instanceof LocationCreateStruct )
        {
            $spiLocation = $this->persistence->locationHandler()->load( $target->parentLocationId );
            $spiContentInfo = $this->persistence->contentHandler()->loadContentInfo( $spiLocation->contentId );
            $parentOwnerId = $spiContentInfo->ownerId;
        }
        else if ( $target !== null && !$target instanceof Location )
            throw new InvalidArgumentException( '$target', 'Must be of type: Location' );
        else if ( $target === null )
            return false;
        else // $target is assumed to be parent in this case
            $parentOwnerId = $target->getContentInfo()->ownerId;

        if ( $parentOwnerId === $currentUser->id )
            return true;

        /**
         * As long as SPI userHandler and API UserService does not speak the same language, this is the ugly truth;
         */
        $locationHandler = $this->persistence->locationHandler();
        $parentOwnerLocations = $locationHandler->loadLocationsByContent( $parentOwnerId );
        if ( empty( $parentOwnerLocations ) )
            return false;

        $currentUserLocations = $locationHandler->loadLocationsByContent( $currentUser->id );
        if ( empty( $currentUserLocations ) )
           return false;

        // @todo Needs to take care of inherited groups as well when UserHandler gets knowledge about user groups
        foreach ( $parentOwnerLocations as $parentOwnerLocation )
        {
            foreach ( $currentUserLocations as $currentUserLocation )
            {
                if ( $parentOwnerLocation->parentId === $currentUserLocation->parentId )
                    return true;
            }
        }

        return false;
    }

    /**
     * Returns Criterion for use in find() query
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $value
     * @param \eZ\Publish\API\Repository\Values\User\User $currentUser
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface
     */
    public function getCriterion( APILimitationValue $value, APIUser $currentUser )
    {
        throw new \eZ\Publish\API\Repository\Exceptions\NotImplementedException( __METHOD__ );
    }

    /**
     * Returns info on valid $limitationValues
     *
     * @return mixed[]|int In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_ constants.
     */
    public function valueSchema()
    {
        throw new \eZ\Publish\API\Repository\Exceptions\NotImplementedException( __METHOD__ );
    }
}
