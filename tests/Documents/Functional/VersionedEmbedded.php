<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="versioned_embedded_test") */
class VersionedEmbedded
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Int @ODM\Version */
    public $version = 1;
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedMany(targetDocument="Embedded") */
    public $embedded = array();

    public function addEmbedded(Embedded $embedded)
    {
        $this->embedded[] = $embedded;
    }
}