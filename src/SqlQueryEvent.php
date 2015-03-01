<?php

namespace Aztech\LiveDebug;

class SqlQueryEvent extends MessageEvent {

    private $executionTime;

    private $parameterDump;

    public function __construct($query, $parameterDump, $executionTime = 0)
    {
        parent::__construct('sql', $query);

        $this->parameterDump = $parameterDump;
        $this->executionTime = $executionTime;
    }

    public function getParameterDump()
    {
        return $this->parameterDump;
    }

    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    public function setExecutionTime($duration)
    {
        $this->executionTime = $duration;
    }
}