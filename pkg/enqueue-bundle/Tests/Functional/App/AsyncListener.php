<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\AsyncEventDispatcher\Commands;
use Enqueue\AsyncEventDispatcher\Registry;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Symfony\Component\EventDispatcher\Event;

class AsyncListener extends \Enqueue\AsyncEventDispatcher\AsyncListener
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param ProducerInterface $producer
     * @param Registry          $registry
     */
    public function __construct(ProducerInterface $producer, Registry $registry)
    {
        $this->producer = $producer;
        $this->registry = $registry;
    }

    /**
     * @param Event  $event
     * @param string $eventName
     */
    public function onEvent(Event $event = null, $eventName)
    {
        if (false == $this->isSyncMode($eventName)) {
            $transformerName = $this->registry->getTransformerNameForEvent($eventName);

            $interopMessage = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
            $message = new Message($interopMessage->getBody());
            $message->setScope(Message::SCOPE_APP);
            $message->setProperty('event_name', $eventName);
            $message->setProperty('transformer_name', $transformerName);

            $this->producer->sendCommand(Commands::DISPATCH_ASYNC_EVENTS, $message);
        }
    }
}
