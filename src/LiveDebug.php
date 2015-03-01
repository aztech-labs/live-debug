<?php

namespace Aztech\LiveDebug;

use Aztech\Events\Bus\Events;
use Aztech\Events\Bus\Plugins\Wamp\WampPluginFactory;
use Aztech\Events\Bus\Serializer\JsonSerializer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\VarDumper;

class LiveDebug
{

    private $publisher;

    public function __construct($socketEndpoint, $socketRealm = null)
    {
        $factory = Events::createFactory(
            new WampPluginFactory(),
            new JsonSerializer(false)
        );

        $this->publisher = $factory->createPublisher(array(
            'endpoint' => $socketEndpoint,
            'realm' => $socketRealm ?: 'io.aztech.livedebug',
            'topic' => 'livedebug'
        ));
    }

    public function logQueries(EntityManager $manager)
    {
        $manager
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(new DoctrineQueryLogger($this->publisher, new VarCloner(), new JsonDumper()));
    }

    public function logKernelEvents(EventDispatcher $dispatcher)
    {
        $callback = function () {
            VarDumper::dump(func_get_args());
        };

        $dispatcher->addListener(KernelEvents::REQUEST, $callback);
        $dispatcher->addListener(KernelEvents::CONTROLLER, $callback);
        $dispatcher->addListener(KernelEvents::EXCEPTION, $callback);
        $dispatcher->addListener(KernelEvents::FINISH_REQUEST, $callback);
        $dispatcher->addListener(KernelEvents::RESPONSE, $callback);
        $dispatcher->addListener(KernelEvents::TERMINATE, $callback);
        $dispatcher->addListener(KernelEvents::VIEW, $callback);
    }

    public function setup()
    {
        $this->bindVarDumper();
    }

    private function bindVarDumper()
    {
        VarDumper::setHandler(call_user_func(function ($publisher) {
            $cloner = new VarCloner();

            return function ($var) use ($publisher, $cloner) {
                $dump = (new JsonDumper())->dump($cloner->cloneVar($var));

                if (! is_array($dump)) {
                    $dump = [ $dump ];
                }

                $publisher->publish(new ObjectDumpEvent($dump));
            };
        }, $this->publisher));
    }
}