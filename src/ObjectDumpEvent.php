<?php

namespace Aztech\LiveDebug;

use Aztech\Events\Event;
use Rhumsaa\Uuid\Uuid;

class ObjectDumpEvent implements Event
{
    /**
     * @var Uuid
     */
    private $id;

    /**
     * @var object
     */
    private $object;

    /**
     * @param array $objectDump
     */
    public function __construct(array $objectDump)
    {
        $this->id = (string) Uuid::uuid4();
        $this->object = $objectDump;
    }

    /**
     * Returns the associated message
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Returns the event ID.
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the category of the event.
     * @return string
     */
    public function getCategory()
    {
        return 'debug.dump';
    }
}