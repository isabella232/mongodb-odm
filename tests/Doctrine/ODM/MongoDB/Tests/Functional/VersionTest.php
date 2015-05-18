<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Phonenumber;

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
     * Ensure the ODM can save a database record with embedded documents in an atomic fashion when atomicSetArray is
     * used.
     *
     * I was not sure to to simulate a concurrent PHP request triggering precisely at the moment where I suffer data
     * corruption related to https://github.com/doctrine/mongodb-odm/pull/1094
     *
     * Since PHP blocks and cannot yield, the best way I thought of to simulate the known problem condition is to
     * put a closure in the global scope and check for it at runtime. If someone has a better way to do this, feel free
     * to improve on this solution.
     */
    public function testDataConsistencyUsingEmbeddedDocuments()
    {
        global $concurrentPHPRequestSimulatedLogic;

        // Create a record.
        $user = new \Documents\UserVersioned();
        $this->dm->persist($user);
        $this->dm->flush($user);
        $this->dm->clear();

        // Imagine a PHP request comes in which modifies the user object.
        $request1dm = $this->createTestDocumentManager();
        $request1User = $request1dm->find('Documents\UserVersioned', $user->getId());
        $request1User->setUsername('ae7e3a92-2983-4cc0-97fb-f074ebd0cabd');
        $request1User->addPhonenumber(new PhoneNumber('1111111111'));

        // Before the first PHP request saves it's changes, simulate a concurrent PHP request for the user record which
        // will attempt to load the record at a moment known to cause data loss and corruption.
        $request2dm = $this->createTestDocumentManager();
        $request2User = null;
        $concurrentPHPRequestSimulatedLogic = function () use ($request2dm, $user, &$request2User) {
            // Simulate a concurrent request loading the database record triggered while request #1 is writing the record.
            $request2User = $request2dm->find('Documents\UserVersioned', $user->getId());
        };

        // Trigger the flush event by PHP request #1.
        $request1dm->flush($request1User);

        // Prove that the second requests loaded version #2 of the record in a consistent state (having the phone number).
        $this->assertEquals(2, $request2User->version);
        $this->assertEquals("ae7e3a92-2983-4cc0-97fb-f074ebd0cabd", $request2User->getUsername());
        $this->assertEquals(1, $request2User->getPhonenumbers()->count(), "Version #2 of the versioned user object should have a phone number embedded document. However, {$request2User->getPhonenumbers()->count()} phone number embedded documents were found. The mongo log was as follows:\n\n{$this->mongoLog}");
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
