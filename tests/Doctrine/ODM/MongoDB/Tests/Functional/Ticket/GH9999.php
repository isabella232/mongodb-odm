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
    public function test_testy()
    {
        $this->dm->persist($this->createBook("title", "author", 'book1'));
        $this->dm->flush();
        $this->dm->persist($this->createBook("title2", "author2", 'book2'));
        $this->dm->flush();
        $this->dm->persist($this->createBook("title_updated", 'author_updated', 'book1'));
        $this->dm->flush();
        $this->dm->persist($this->createBook("title2_updated", 'author2_updated', 'book2'));
        $this->dm->flush();

        $book = $this->dm->getRepository(GH9999Book::CLASSNAME)->findOneBy(array('_id' => 'book1'));
        $this->assertTrue($book->getTitle() == 'title_updated');
        $book = $this->dm->getRepository(GH9999Book::CLASSNAME)->findOneBy(array('_id' => 'book2'));
        $this->assertTrue($book->getTitle() == 'title2_updated');


    }
}
/**
 * @ODM\Document
 */
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