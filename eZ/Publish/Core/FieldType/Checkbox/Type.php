<?php
/**
 * File containing the Checkbox class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\FieldType\Checkbox;
use eZ\Publish\Core\FieldType\FieldType,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentType,
    eZ\Publish\Core\FieldType\ValidationError;

/**
 * Checkbox field type.
 *
 * Represent boolean values.
 */
class Type extends FieldType
{
    /**
     * Build a Value object of current FieldType
     *
     * Build a FiledType\Value object with the provided $boolValue as value.
     *
     * @param bool $boolValue
     * @return \eZ\Publish\Core\FieldType\Checkbox\Value
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function buildValue( $boolValue )
    {
        return new Value( $boolValue );
    }

    /**
     * Return the field type identifier for this field type
     *
     * @return string
     */
    public function getFieldTypeIdentifier()
    {
        return "ezboolean";
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \eZ\Publish\Core\FieldType\Checkbox\Value
     */
    public function getDefaultDefaultValue()
    {
        return new Value( false );
    }

    /**
     * Checks the type and structure of the $Value.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the parameter is not of the supported value sub type
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the value does not match the expected structure
     *
     * @param \eZ\Publish\Core\FieldType\Checkbox\Value $inputValue
     *
     * @return \eZ\Publish\Core\FieldType\Checkbox\Value
     */
    public function acceptValue( $inputValue )
    {
        if ( !$inputValue instanceof Value )
        {
            throw new InvalidArgumentType(
                '$inputValue',
                'eZ\\Publish\\Core\\FieldType\\Checkbox\\Value',
                $inputValue
            );
        }

        if ( !is_bool( $inputValue->bool ) )
        {
            throw new InvalidArgumentType(
                '$inputValue->bool',
                'boolean',
                $inputValue->bool
            );
        }

        return $inputValue;
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @return array
     */
    protected function getSortInfo( $value )
    {
        return (int) $value->bool;
    }

    /**
     * Converts an $hash to the Value defined by the field type
     *
     * @param mixed $hash
     *
     * @return \eZ\Publish\Core\FieldType\Checkbox\Value $value
     */
    public function fromHash( $hash )
    {
        return new Value( $hash );
    }

    /**
     * Converts a $Value to a hash
     *
     * @param \eZ\Publish\Core\FieldType\Checkbox\Value $value
     *
     * @return mixed
     */
    public function toHash( $value )
    {
        return $value->bool;
    }

    /**
     * Returns whether the field type is searchable
     *
     * @return bool
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * Get index data for field data for search backend
     *
     * @param mixed $value
     * @return \eZ\Publish\SPI\Persistence\Content\Search\DocumentField[]
     */
    public function getIndexData( $value )
    {
        throw new \RuntimeExcepion( '@TODO: Implement' );
    }
}
