<?php

namespace Aztech\LiveDebug;

use Aztech\Events\Bus\Publisher;
use Doctrine\DBAL\Logging\SQLLogger;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DoctrineQueryLogger implements SQLLogger
{

    private $publisher;

    private $queries;

    private $cloner;

    private $dumper;

    public function __construct(Publisher $publisher, VarCloner $cloner, JsonDumper $dumper)
    {
        $this->cloner = $cloner;
        $this->dumper = $dumper;
        $this->publisher = $publisher;
        $this->queries = new \SplStack();
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string $sql The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $dump = $this->dumper->dump($this->cloner->cloneVar($params));

        $this->queries->push([ new SqlQueryEvent($sql, $dump), microtime(true) ]);
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        if ($this->queries->isEmpty()) {
            return;
        }

        list($query, $startTime) = $this->queries->pop();

        $query->setExecutionTime(microtime(true) - $startTime);

        $this->publisher->publish($query);
    }
}