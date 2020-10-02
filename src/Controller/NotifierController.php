<?php

declare(strict_types=1);

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Transport;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/notify", name="send_notifier")
 * @Template("base.html.twig")
 */
final class NotifierController extends AbstractController
{
    private $notifier;
    private $chatter;
    private $texter;
    private $dispatcher;

    public function __construct(NotifierInterface $notifier, ChatterInterface $chatter, TexterInterface $texter, EventDispatcherInterface $dispatcher)
    {
        $this->notifier = $notifier;
        $this->chatter = $chatter;
        $this->texter = $texter;
        $this->dispatcher = $dispatcher;
    }

    public function __invoke()
    {
        $notification = (new Notification())
            ->subject('A nice subject')
            ->content('An even nicer content.')
            ->importance(Notification::IMPORTANCE_URGENT)
        ;

        $this->notifier->send($notification);

        $notification->channels(['sms/twilio']);
        $this->notifier->send($notification, $this->notifier->getAdminRecipients()[0]);

        $chatMessage = ChatMessage::fromNotification($notification);
        $this->chatter->send($chatMessage);
        $smsMessage = SmsMessage::fromNotification($notification, $this->notifier->getAdminRecipients()[0]);
        $this->texter->send($smsMessage);

        $chatMessage = ChatMessage::fromNotification($notification);
        $chatMessage->transport('slack');
        $this->chatter->send($chatMessage);

        $smsMessage = SmsMessage::fromNotification($notification, $this->notifier->getAdminRecipients()[0]);
        $smsMessage->transport('twilio');
        $this->texter->send($smsMessage);

        $chatMessage = ChatMessage::fromNotification($notification);
        Transport::fromDsn('null://null', $this->dispatcher)
            ->send($chatMessage);

        $smsMessage = SmsMessage::fromNotification($notification, $this->notifier->getAdminRecipients()[0]);
        Transport::fromDsn('null://null', $this->dispatcher)
            ->send($smsMessage);

        return [];
    }
}
