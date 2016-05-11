<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class G9999Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function createBook($title, $author, $id)
    {
        $book = (new GH9999Book())
            ->setAuthor($author)
            ->setTitle($title)
            ->setId($id);

        return $book;
    }

    public function createBookWithTitle($title, $id)
    {
        $book = (new GH9999Book())
            ->setTitle($title)
            ->setId($id);

        return $book;
    }

    public function createBookWithAuthor($author, $id)
    {
        $book = (new GH9999Book())
            ->setAuthor($author)
            ->setId($id);

        return $book;
    }
    public function test_testy()
    {
        $this->dm->persist($this->createBookWithTitle("title",'book1'));
        $this->dm->flush();
        $this->dm->persist($this->createBookWithAuthor('author', 'book2'));
        $this->dm->flush();
        $this->dm->persist($this->createBookWithTitle("title2",'book1'));
        $this->dm->flush();
        $this->dm->persist($this->createBookWithAuthor('author2', 'book2'));
        $this->dm->flush();
        $book = $this->dm->getRepository(GH9999Book::CLASSNAME)->findOneBy(array('_id' => 'book1'));
        $this->assertNotNull($book);
        var_dump($book);
        $this->assertTrue($book->getTitle() == 'title2');
        $this->assertNull($book->getAuthor());
        $book = $this->dm->getRepository(GH9999Book::CLASSNAME)->findOneBy(array('_id' => 'book2'));
        $this->assertNotNull($book);
        var_dump($book);
        $this->assertTrue($book->getAuthor() == 'author2');
        $this->assertNull($book->getTitle());
    }
}
/** @ODM\Document */
class GH9999Book
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id(strategy="none", type="string") */
    protected $id;

    /** @ODM\String */
    private $author;

    /** @ODM\String */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
}