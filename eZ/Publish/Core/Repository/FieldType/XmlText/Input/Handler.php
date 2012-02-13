<?php
/**
 * File containing the eZ\Publish\Core\Repository\FieldType\XmlText\Input\Handler\Simplified class.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Repository\FieldType\XmlText\Input;

use ezp\Base\Repository,
    ezp\Content\Version,
    eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser as InputParserInterface,
    eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser\Base as BaseInputParser,
    ezp\Content\Relation,
    DOMDocument;

/**
 * Simplified XmlText input handler
 */
class Handler
{
    /**
     * XmlText parser
     * @var \eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser
     */
    protected $parser;

    /**
     * DOMDocument, as processed
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Construct a new Simplified InputHandler$xmlString
     *
     * @param string $xmlString
     * @param \eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser Parser
     */
    public function __construct( InputParserInterface $parser )
    {
        $this->parser = $parser;
        $this->parser->setHandler( $this );
        // $this->xmlString = preg_replace( '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $xmlString, -1, $count );
        /*if ( $count > 0 )
           {
           eZDebug::writeWarning( "$count invalid character(s) detected. They have been removed from input.", __METHOD__ );
           }*/
    }

    /**
     * Checks if $xmlString is a valid XML
     * @param string $xmlString
     * @param bool $checkExternalData Wether or not to check external data (content, location...) validity
     *        If the option is set to false, the tags will be checked, but not the external elements they reference
     * @return bool
     */
    public function isXmlValid( $xmlString, $checkExternalData = true )
    {
        $this->parser->setOption( BaseInputParser::OPT_VALIDATE_ERROR_LEVEL, BaseInputParser::ERROR_ALL );
        $this->parser->setOption( BaseInputParser::OPT_DETECT_ERROR_LEVEL, BaseInputParser::ERROR_ALL );
        $this->parser->setOption( BaseInputParser::OPT_PARSE_LINE_BREAKS, false );
        $this->parser->setOption( BaseInputParser::OPT_REMOVE_DEFAULT_ATTRS, false );

        $this->parser->setOption( BaseInputParser::OPT_CHECK_EXTERNAL_DATA, $checkExternalData );

        $document = $this->parser->process( $xmlString );

        // @todo instanceof
        return $document instanceof DOMDocument;
    }

    /**
     * Returns the last parsing messages (from the last parsing operation, {@see isXmlValid}, {@see process})
     * @return array
     */
    public function getParsingMessages()
    {
        return $this->parser->getMessages();
    }

    /**
     * Processes $xmlString and indexes the external data it references
     * @param string $xmlString
     * @param \ezp\Base\Repository $repository
     * @param \ezp\Content\Version $version
     * @return bool
     */
    public function process( $xmlString, Repository $repository, Version $version )
    {
        $this->parser->setOption( BaseInputParser::OPT_CHECK_EXTERNAL_DATA, true );

        $document = $this->parser->process( $xmlString );

        if ( !$document instanceof DOMDocument )
        {
            return false;
        }
        $this->document = $document;

        $service = $repository->getInternalFieldTypeService();

        // related content
        foreach ( $this->parser->getRelatedContentIdArray() as $contentId )
        {
            $service->addRelation( Relation::ATTRIBUTE, $version->contentId, $version->versionNo, $contentId );
        }

        // linked content
        foreach ( $this->parser->getLinkedContentIdArray() as $contentId )
        {
            $service->addRelation( Relation::LINK, $version->contentId, $version->versionNo, $contentId );
        }

        return true;
    }

    /**
     * Callback that gets a location from its id
     * @param mixed $locationId
     * @return \ezp\Content\Location
     */
    public function getLocationById( $locationId )
    {
        return false;
    }

    /**
     * Callback that gets a location from its path
     * @param string $locationPath
     * @return \ezp\Content\Location
     */
    public function getLocationByPath( $locationPath )
    {
        return false;
    }

    /**
     * Callback that gets a content from its id
     * @param int $contentId
     * @return \ezp\Content
     */
    public function getContentById( $contentId )
    {
        return false;
    }

    /**
     * Registers an external URL
     * @param string $url
     * @return Url
     * @todo Implement & Document
     */
    public function registerUrl( $url )
    {
        return false;
    }

    /**
     * Checks if a Content exists using its id
     * @param int $contentId
     * @return bool true if the Content exists, false otherwise
     */
    public function checkContentById( $contentId )
    {
        return false;
    }

    /**
     * Returns the processed DOMDocument as an XML string
     * @return string
     */
    public function getDocumentAsXml()
    {
        if ( $this->document instanceof DOMDocument )
        {
            return $this->document->saveXML();
        }
        else
        {
            return false;
        }
    }
}
?>
