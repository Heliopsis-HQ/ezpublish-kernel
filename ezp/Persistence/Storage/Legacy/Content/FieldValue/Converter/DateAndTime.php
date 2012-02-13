<?php
/**
 * File containing the DateAndTime converter
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Persistence\Storage\Legacy\Content\FieldValue\Converter;
use ezp\Persistence\Storage\Legacy\Content\FieldValue\Converter,
    ezp\Persistence\Storage\Legacy\Content\StorageFieldValue,
    ezp\Persistence\Content\FieldValue,
    ezp\Persistence\Content\Type\FieldDefinition,
    ezp\Persistence\Storage\Legacy\Content\StorageFieldDefinition,
    eZ\Publish\Core\Repository\FieldType\DateAndTime\Type as DateAndTimeType,
    eZ\Publish\Core\Repository\FieldType\DateAndTime\Value as DateAndTimeValue,
    eZ\Publish\Core\Repository\FieldType\FieldSettings,
    ezp\Base\Exception\InvalidArgumentType,
    DateTime,
    DateInterval,
    DOMDocument,
    SimpleXMLElement;

class DateAndTime implements Converter
{
    /**
     * Converts data from $value to $storageFieldValue
     *
     * @param \ezp\Persistence\Content\FieldValue $value
     * @param \ezp\Persistence\Storage\Legacy\Content\StorageFieldValue $storageFieldValue
     */
    public function toStorageValue( FieldValue $value, StorageFieldValue $storageFieldValue )
    {
        $storageFieldValue->dataInt = 0;
        if ( $value->data->value instanceof DateTime )
            $storageFieldValue->dataInt = $value->data->value->getTimestamp();

        $storageFieldValue->sortKeyInt = $value->sortKey['sort_key_int'];
    }

    /**
     * Converts data from $value to $fieldValue
     *
     * @param \ezp\Persistence\Storage\Legacy\Content\StorageFieldValue $value
     * @param \ezp\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue( StorageFieldValue $value, FieldValue $fieldValue )
    {
        $date = new DateTime;
        $date->setTimestamp( $value->dataInt );

        $fieldValue->data = new DateAndTimeValue( $date );
        $fieldValue->sortKey = array( 'sort_key_int' => $value->sortKeyInt );
    }

    /**
     * Converts field definition data in $fieldDef into $storageFieldDef
     *
     * @param \ezp\Persistence\Content\Type\FieldDefinition $fieldDef
     * @param \ezp\Persistence\Storage\Legacy\Content\StorageFieldDefinition $storageDef
     */
    public function toStorageFieldDefinition( FieldDefinition $fieldDef, StorageFieldDefinition $storageDef )
    {
        $storageDef->dataInt2 = $fieldDef->fieldTypeConstraints->fieldSettings['useSeconds'] ? 1 : 0;
        $storageDef->dataInt1 = $fieldDef->fieldTypeConstraints->fieldSettings['defaultType'];

        if ( $fieldDef->fieldTypeConstraints->fieldSettings['defaultType'] === DateAndTimeType::DEFAULT_CURRENT_DATE_ADJUSTED )
        {
            $storageDef->dataText5 = $this->generateDateIntervalXML(
                $fieldDef->fieldTypeConstraints->fieldSettings['dateInterval']
            );
        }
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef
     *
     * @param \ezp\Persistence\Storage\Legacy\Content\StorageFieldDefinition $storageDef
     * @param \ezp\Persistence\Content\Type\FieldDefinition $fieldDef
     */
    public function toFieldDefinition( StorageFieldDefinition $storageDef, FieldDefinition $fieldDef )
    {
        $useSeconds = (bool)$storageDef->dataInt2;
        $dateInterval = $this->getDateIntervalFromXML( $storageDef->dataText5 );

        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings(
            array(
                'defaultType' => $storageDef->dataInt1,
                'useSeconds' => $useSeconds,
                'dateInterval' => $dateInterval
            )
        );

        // Building default value
        switch ( $fieldDef->fieldTypeConstraints->fieldSettings['defaultType'] )
        {
            case DateAndTimeType::DEFAULT_CURRENT_DATE:
                $date = new DateTime;
                break;

            case DateAndTimeType::DEFAULT_CURRENT_DATE_ADJUSTED:
                if ( !$useSeconds )
                    $dateInterval->s = 0;
                $date = new DateTime;
                $date->add( $dateInterval );
                break;

            default:
                $date = null;
        }
        $fieldDef->defaultValue->data = new DateAndTimeValue( $date );
    }

    /**
     * Returns the name of the index column in the attribute table
     *
     * Returns the name of the index column the datatype uses, which is either
     * "sort_key_int" or "sort_key_string". This column is then used for
     * filtering and sorting for this type.
     *
     * @return string
     */
    public function getIndexColumn()
    {
        return 'sort_key_int';
    }

    /**
     * Generates the internal XML structure for $dateInterval, used for date adjustment
     *
     * @param \DateInterval $dateInterval
     * @return string The generated XML string
     */
    protected function generateDateIntervalXML( DateInterval $dateInterval )
    {
        // Constructing XML structure
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'adjustment' );

        $year = $doc->createElement( 'year' );
        $year->setAttribute( 'value', $dateInterval->format( '%y' ) );
        $root->appendChild( $year );
        unset( $year );

        $month = $doc->createElement( 'month' );
        $month->setAttribute( 'value', $dateInterval->format( '%m' ) );
        $root->appendChild( $month );
        unset( $month );

        $day = $doc->createElement( 'day' );
        $day->setAttribute( 'value', $dateInterval->format( '%d' ) );
        $root->appendChild( $day );
        unset( $day );

        $hour = $doc->createElement( 'hour' );
        $hour->setAttribute( 'value', $dateInterval->format( '%h' ) );
        $root->appendChild( $hour );
        unset( $hour );

        $minute = $doc->createElement( 'minute' );
        $minute->setAttribute( 'value', $dateInterval->format( '%i' ) );
        $root->appendChild( $minute );
        unset( $minute );

        $second = $doc->createElement( 'second' );
        $second->setAttribute( 'value', $dateInterval->format( '%s' ) );
        $root->appendChild( $second );
        unset( $second );

        $doc->appendChild( $root );
        return $doc->saveXML();
    }

    /**
     * Generates a DateInterval object from $xmlText
     *
     * @param string $xmlText
     * @return \DateInterval
     */
    protected function getDateIntervalFromXML( $xmlText )
    {
        if ( empty( $xmlText ) )
            return;

        $xml = new SimpleXMLElement( $xmlText );
        $aIntervalString = array(
            (int)$xml->year['value'] . ' years',
            (int)$xml->month['value'] . ' months',
            (int)$xml->day['value'] . ' days',
            (int)$xml->hour['value'] . ' hours',
            (int)$xml->minute['value'] . ' minutes',
            (int)$xml->second['value'] . ' seconds',
        );

        return DateInterval::createFromDateString( implode( ', ', $aIntervalString ) );
    }
}
