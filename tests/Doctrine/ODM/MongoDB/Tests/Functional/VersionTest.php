<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Functional\VersionedEmbedded;
use Documents\Functional\Embedded;

class VersionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function tearDown()
    {
        global $concurrentRequestLogic;
        if (isset($concurrentRequestLogic)) {
            unset($concurrentRequestLogic);
        }
        unset($concurrentRequestLogic);
        parent::tearDown();
    }

    public function testVersioningWhenManipulatingEmbedMany()
    {
        $expectedVersion = 1;
        $doc = new VersionedDocument();
        $doc->name = 'test';
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 1');
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 2');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 3');
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
        
        $doc->embedMany[0]->embedMany[] = new VersionedEmbeddedDocument('deeply embed 1');
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
        
        unset($doc->embedMany[1]);
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany->clear();
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version); 
        
        $doc->embedMany = null;
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
    }


    /**
     * The ODM does not save database records with embedded documents in an atomic fashion.
     *
     * Even with ODM versioning, a concurrent PHP request which happens to load a Mongo record while another PHP
     * request is writing the same record will load an incomplete and inconsistent version of the Mongo record into a
     * Doctrine ODM model. This has been a repeated cause of data loss and corruption.
     */
    public function testDataConsistencyUsingEmbeddedDocuments()
    {
        global $concurrentPHPRequestSimulatedLogic;
        $document = new \Documents\Functional\VersionedEmbedded();
        $document->name = "Jon";
        $this->dm->persist($document);
        $this->dm->flush($document);
        $this->dm->clear();
        
        // Imagine a PHP request comes in which modifies the document.
        $request1dm = $this->createTestDocumentManager();
        $request1Document = $request1dm->find('Documents\Functional\VersionedEmbedded', $document->id);
        $request1Document->name = "Jonathan";
        $embeddedDocument = new Embedded();
        $embeddedDocument->name = "I am an embedded document.";
        $request1Document->addEmbedded($embeddedDocument);
        
        // Simulate a concurrent PHP request for the document.
        $request2dm = $this->createTestDocumentManager();
        $request2Document = null;
        $concurrentPHPRequestSimulatedLogic = function () use ($request2dm, $document, &$request2Document) {
            // Simulate a concurrent request loading the database record triggered while request #1 is writing the record.
            $request2Document = $request2dm->find('Documents\Functional\VersionedEmbedded', $document->id);
        };
        
        // Trigger the flush event by PHP request #1.
        $request1dm->flush($request1Document);

        // Prove that concurrent requests can load version #2 of the record in a consistent state.
        $this->assertEquals(2, $request2Document->version);
        $this->assertEquals("Jonathan", $request2Document->name);
        $this->assertEquals(1, $request2Document->embedded->count(), "Version #2 of the this document should have an embedded document. However, {$request2Document->embedded->count()} embedded documents were found.");
    }
}

/**
 * @ODM\Document
 */
class VersionedDocument
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\Int @ODM\Version */
    public $version = 1;
    
    /** @ODM\String */
    public $name;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany = array();
    
    public function __construct()
    {
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class VersionedEmbeddedDocument
{
    /** @ODM\String */
    public $value;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany;
    
    public function __construct($value) 
    {
        $this->value = $value;
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
