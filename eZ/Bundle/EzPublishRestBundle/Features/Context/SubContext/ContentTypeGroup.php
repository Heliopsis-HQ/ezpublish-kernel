<?php
/**
 * File containing the ContentTypeGroup context class for RestBundle.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Bundle\EzPublishRestBundle\Features\Context\SubContext;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use EzSystems\BehatBundle\Sentence\ContentTypeGroup as ContentTypeGroupSentences;
use Behat\Behat\Context\Step;
use Behat\Gherkin\Node\TableNode;
use PHPUnit_Framework_Assert as Assertion;

class ContentTypeGroup extends Base implements ContentTypeGroupSentences
{
    public function iReadContentTypeGroup( $identifier )
    {
        $this->getMainContext()->setLastAction( "read" );

        return array(
            new Step\When( 'I create a "GET" request to "/content/typegroups?identifier=' . $identifier . '"' ),
            // this next step is needed for guzzle client
            new Step\When( 'I add "accept" header with a "ContentTypeGroup"' ),
            new Step\When( 'I send the request' )
        );
    }

    public function iReadContentTypeGroupsList()
    {
        $this->getMainContext()->setLastAction( "read" );

        return array(
            new Step\When( 'I create a "GET" request to "/content/typegroups"' ),
            new Step\When( 'I add "accept" header to "List" a "ContentTypeGroup"' ),
            new Step\When( 'I send the request' )
        );
    }

    public function iCreateContentTypeGroup( $identifier )
    {
        $this->getMainContext()->setLastAction( "create" );

        return array(
            new Step\When( 'I create a "POST" request to "/content/typegroups"' ),
            new Step\When( 'I add "content-type" header with "Input" for "ContentTypeGroup"' ),
            new Step\When( 'I add "accept" header for a "ContentTypeGroup"' ),
            new Step\When( 'I make a "ContentTypeGroupCreateStruct" object' ),
            new Step\When( 'I add "' . $identifier . '" value to "identifier" field' ),
            new Step\When( 'I send the request' )
        );
    }

    public function iUpdateContentTypeGroupIdentifier( $actualIdentifier, $newIdentifier )
    {
        $this->getMainContext()->setLastAction( "update" );

        $repository = $this->getMainContext()->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        // load the ContentTypeGroup to be updated
        $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier( $actualIdentifier );

        return array(
            new Step\When( 'I create a "PATCH" request to "/content/typegroups/' . $contentTypeGroup->id . '"' ),
            new Step\When( 'I add "content-type" header with "Input" for "ContentTypeGroup"' ),
            new Step\When( 'I add "accept" header for a "ContentTypeGroup"' ),
            new Step\When( 'I make a "ContentTypeGroupUpdateStruct" object' ),
            new Step\When( 'I add "' . $newIdentifier . '" value to "identifier" field' ),
            new Step\When( 'I send the request' )
        );
    }

    public function iDeleteContentTypeGroup( $identifier )
    {
        $this->getMainContext()->setLastAction( "delete" );

        $repository = $this->getMainContext()->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        // load the ContentTypeGroup to be updated
        $contentTypeGroup = $repository->sudo(
            function() use( $contentTypeService, $identifier )
            {
                return $contentTypeService->loadContentTypeGroupByIdentifier( $identifier );
            }
        );

        return array(
            new Step\When( 'I create a "DELETE" request to "/content/typegroups/' . $contentTypeGroup->id . '"' ),
            new Step\When( 'I send the request' )
        );
    }

    public function iSeeContentTypeGroup( $identifier )
    {
        // verify that the object exist
        $repository = $this->getMainContext()->getRepository();

        // verify ContentTypeGroup with $identifier exist
        $repository->sudo(
            function() use ( $repository, $identifier )
            {
                $repository->getContentTypeService()->loadContentTypeGroupByIdentifier( $identifier );
            }
        );

        // check if response should be tested/verified
        if ( !$this->getMainContext()->shouldVerifyResponse() )
        {
            return;
        }

        // if it is check it up
        list( $code, $message ) = $this->getMainContext()->getLastActionStatusCodeAndMessage();
        return array(
            new Step\Then( 'I see ' . $code . ' status code' ),
            new Step\Then( 'I see "' . $message . '" status message' ),
            new Step\Then( 'I see "content-type" header with a "ContentTypeGroup"' ),
            new Step\Then( 'I see response body with "eZ\\Publish\\Core\\REST\\Client\\Values\\ContentType\\ContentTypeGroup" object' ),
            new Step\Then( 'I see response object field "identifier" with "' . $identifier . '" value' )
        );
    }

    public function iDonTSeeAContentTypeGroup( $identifier )
    {
        /** @var \eZ\Publish\API\Repository\Repository $repository */
        $repository = $this->getMainContext()->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        // verify if the content type group exists
        try
        {
            $contentTypeGroup = $repository->sudo(
                function() use( $identifier, $contentTypeService )
                {
                    return $contentTypeService->loadContentTypeGroupByIdentifier( $identifier );
                }
            );

            Assertion::assertEmpty(
                $contentTypeGroup,
                "Not expected Content Type Group  with '$identifier' identifier found"
            );
        }
        catch ( NotFoundException $e )
        {
            // do nothing
        }

        // @todo: verify status code / message / content type / accept header
    }

    public function iSeeTotalContentTypeGroup( $total, $identifier )
    {
        $repository = $this->getMainContext()->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        // get all content type groups
        $contentTypeGroupList = $repository->sudo(
            function() use ( $contentTypeService )
            {
                return $contentTypeService->loadContentTypeGroups();
            }
        );

        // count how many are found with $identifier
        $count = 0;
        foreach ( $contentTypeGroupList as $contentTypeGroup )
        {
            if ( $contentTypeGroup->identifier === $identifier )
            {
                $count++;
            }
        }

        Assertion::assertEquals(
            $total,
            $count,
            "Expected '$total' ContentTypeGroups with '$identifier' identifier but found '$count'"
        );

        // @todo: verify status code / message / content type / accept header
    }

    public function iSeeTheFollowingContentTypeGroups( TableNode $table )
    {
        // get groups
        $groups = $this->getMainContext()->getSubContext( 'Common' )->convertTableToArrayOfData( $table );

        // get real ContentTypeGroup identifiers

        // verify if the expects objects are in the list
        foreach ( $this->getMainContext()->getResponseObject() as $ContentTypeGroup )
        {
            $found = array_search( $ContentTypeGroup->identifier, $groups );
            if ( $found !== false )
            {
                unset( $groups[$found] );
            }
        }

        // verify if all the expected groups were found
        Assertion::assertEmpty(
            $groups,
            "Expected to find all groups but couldn't find: " . print_r( $groups, true )
        );

        // @todo: verify status code / message / content type / accept header
    }
}
