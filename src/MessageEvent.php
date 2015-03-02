<?php

namespace Aztech\LiveDebug;

use Aztech\Events\Event;
use Rhumsaa\Uuid\Uuid;

class MessageEvent implements Event
{
    /**
     * @var Uuid
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $message;

    /**
     * @param $type
     * @param string $message
     */
    public function __construct($type, $message)
    {
        $this->id = (string) Uuid::uuid4();
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * Returns the associated message
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the message type
     * @return string
     */
    public function getMessageType()
    {
        return $this->type;
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
        return 'debug.' . strtolower($this->type);
    }
}
