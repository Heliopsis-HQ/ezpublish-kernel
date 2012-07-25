<?php
/**
 * File containing the FieldType class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\FieldType;
use eZ\Publish\API\Repository\Values\Content\Field,
    eZ\Publish\API\Repository\FieldTypeTools,
    eZ\Publish\Core\Repository\ValidatorService,
    eZ\Publish\Core\FieldType\Validator,
    eZ\Publish\SPI\FieldType\FieldType as FieldTypeInterface,
    eZ\Publish\SPI\Persistence\Content\FieldValue,
    eZ\Publish\SPI\Persistence\Content\FieldTypeConstraints,
    eZ\Publish\API\Repository\Values\ContentType\FieldDefinition,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    eZ\Publish\SPI\FieldType\Event;

/**
 * Base class for field types, the most basic storage unit of data inside eZ Publish.
 *
 * All other field types extend FieldType providing the specific functionality
 * desired in each case.
 *
 * The capabilities supported by each individual field type is decided by which
 * interfaces the field type implements support for. These individual
 * capabilities can also be checked via the supports*() methods.
 *
 * Field types are the base building blocks of Content Types, and serve as
 * data containers for Content objects. Therefore, while field types can be used
 * independently, they are designed to be used as a part of a Content object.
 *
 * Field types are primed and pre-configured with the Field Definitions found in
 * Content Types.
 *
 * @todo Merge and optimize concepts for settings, validator data and field type properties.
 */
abstract class FieldType implements FieldTypeInterface
{
    /**
     * The setting keys which are available on this field type.
     *
     * The key is the setting name, and the value is the default value for given
     * setting, set to null if no particular default should be set.
     *
     * @var mixed
     */
    protected $settingsSchema = array();

    /**
     * An array of validator identifiers which are allowed for this FieldType
     *
     * @var string[]
     */
    protected $allowedValidators = array();

    /**
     * Tool object for field types
     *
     * @var \eZ\Publish\API\Repository\FieldTypeTools
     */
    protected $fieldTypeTools;

    /**
     * Holds an instance of validator service
     *
     * @var \eZ\Publish\Core\Repository\ValidatorService
     */
    protected $validatorService;

    /**
     * Constructs field type object, initializing internal data structures.
     *
     * @param \eZ\Publish\Core\Repository\ValidatorService $validatorService
     * @param \eZ\Publish\API\Repository\FieldTypeTools $fieldTypeTools
     * @return void
     */
    public function __construct( ValidatorService $validatorService, FieldTypeTools $fieldTypeTools )
    {
        $this->fieldTypeTools = $fieldTypeTools;
        $this->validatorService = $validatorService;
    }

    /**
     * This method is called on occurring events. Implementations can perform corresponding actions
     *
     * @param \eZ\Publish\SPI\FieldType\Event $event
     */
    public function handleEvent( Event $event )
    {
    }

    /**
     * Returns a schema for the settings expected by the FieldType
     *
     * This implementation returns an array.
     * where the key is the setting name, and the value is the default value for given
     * setting and set to null if no particular default should be set.
     *
     * @return mixed
     */
    public function getSettingsSchema()
    {
        return $this->settingsSchema;
    }

    /**
     * Returns a schema for supported validator configurations.
     *
     * This implementation returns a three dimensional map containing for each validator configuration
     * referenced by identifier a map of supported parameters which are defined by a type and a default value
     * (see example).
     * Example:
     * <code>
     *  array(
     *      'stringLength' => array(
     *          'minStringLength' => array(
     *              'type'    => 'int',
     *              'default' => 0,
     *          ),
     *          'maxStringLength' => array(
     *              'type'    => 'int'
     *              'default' => null,
     *          )
     *      ),
     *  );
     * </code>
     * The validator identifier is mapped to a Validator class which can be retrieved via the
     * ValidatorService.
     */
    public function getValidatorConfigurationSchema()
    {
        $validatorConfigurationSchema = array();
        foreach ( $this->allowedValidators as $validatorIdentifier )
        {
            $validator = $this->validatorService->getValidator( $validatorIdentifier );
            $validatorConfigurationSchema[$validatorIdentifier] = $validator->getConstraintsSchema();
        }

        return $validatorConfigurationSchema;
    }

    /**
     * Validates a field based on the validators in the field definition
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param \eZ\Publish\Core\FieldType\Value $fieldValue The field for which an action is performed
     *
     * @return \eZ\Publish\SPI\FieldType\ValidationError[]
     */
    public function validate( FieldDefinition $fieldDefinition, $fieldValue )
    {
        $errors = array();
        foreach ( (array)$fieldDefinition->getValidatorConfiguration() as $validatorIdentifier => $parameters )
        {
            $validator = $this->validatorService->getValidator( $validatorIdentifier );
            $validator->initializeWithConstraints( $parameters );
            if ( !$validator->validate( $fieldValue ) )
            {
                $errors[] = $validator->getMessage();
            }
        }
        return $errors;
    }

    /**
     * Validates the validatorConfiguration of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct
     *
     * @param mixed $validatorConfiguration
     *
     * @return \eZ\Publish\SPI\FieldType\ValidationError[]
     */
    public function validateValidatorConfiguration( $validatorConfiguration )
    {
        $validationErrors = array();
        foreach ( (array)$validatorConfiguration as $validatorIdentifier => $constraints )
        {
            if ( in_array( $validatorIdentifier, $this->allowedValidators ) )
            {
                $validator = $this->validatorService->getValidator( $validatorIdentifier );
                $validationErrors += $validator->validateConstraints( $constraints );
            }
            else
            {
                $validationErrors[] = new ValidationError(
                    "Validator '%validator%' is unknown",
                    null,
                    array(
                        "validator" => $validatorIdentifier
                    )
                );
            }
        }

        return $validationErrors;
    }

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct
     *
     * @param mixed $fieldSettings
     *
     * @return \eZ\Publish\SPI\FieldType\ValidationError[]
     */
    public function validateFieldSettings( $fieldSettings )
    {
        if ( !empty( $fieldSettings ) )
        {
            return array(
                new ValidationError(
                    "FieldType '%fieldType%' does not accept settings",
                    null,
                    array(
                        "fieldType" => $this->getFieldTypeIdentifier()
                    )
                )
            );
        }

        return array();
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * Return value is mixed. It should be something which is sensible for
     * sorting.
     *
     * It is up to the persistence implementation to handle those values.
     * Common string and integer values are safe.
     *
     * For the legacy storage it is up to the field converters to set this
     * value in either sort_key_string or sort_key_int.
     *
     * @param mixed $value
     * @return mixed
     */
    abstract protected function getSortInfo( $value );

    /**
     * Converts a $value to a persistence value
     *
     * @param mixed $value
     *
     * @return \eZ\Publish\SPI\Persistence\Content\FieldValue
     */
    public function toPersistenceValue( $value )
    {
        // @todo Evaluate if creating the sortKey in every case is really needed
        //       Couldn't this be retrieved with a method, which would initialize
        //       that info on request only?
        return new FieldValue(
            array(
                "data" => $this->toHash( $value ),
                "externalData" => null,
                "sortKey" => $this->getSortInfo( $value ),
            )
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value
     *
     * @param \eZ\Publish\SPI\Persistence\Content\FieldValue $fieldValue
     *
     * @return mixed
     */
    public function fromPersistenceValue( FieldValue $fieldValue )
    {
        return $this->fromHash( $fieldValue->data );
    }

    /**
     * Returns whether the field type is searchable
     *
     * @return bool
     */
    public function isSearchable()
    {
        return false;
    }
}
