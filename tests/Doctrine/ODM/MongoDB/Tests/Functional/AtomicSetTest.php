<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Phonenumber;

class AtomicSetTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAtomicInsertAndUpdate()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $user->surname = "Malarz";
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $newUser->name);
        $this->assertEquals('Malarz', $newUser->surname);
        $this->assertCount(2, $newUser->phonenumbers);
    }
    
    public function testAtomicUpsert()
    {
        $user = new AtomicUser('Maciej');
        $user->id = new \MongoId();
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $newUser->name);
        $this->assertCount(1, $newUser->phonenumbers);
    }
    
    public function testAtomicCollectionUnset()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $user->surname = "Malarz";
        $user->phonenumbers = null;
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $newUser->name);
        $this->assertEquals('Malarz', $newUser->surname);
        $this->assertCount(0, $newUser->phonenumbers);
    }
}

/**
 * @ODM\Document
 */
class AtomicUser
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\String */
    public $name;
    
    /** @ODM\String */
    public $surname;
    
    /** @ODM\EmbedMany(strategy="atomicSet",targetDocument="Documents\Phonenumber") */
    public $phonenumbers;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->phonenumbers = new ArrayCollection();
    }
}
