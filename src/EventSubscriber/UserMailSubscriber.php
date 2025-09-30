<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class UserMailSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $params;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $params)
    {
        $this->mailer = $mailer;
        $this->params = $params;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['sendMail', EventPriorities::POST_WRITE],
        ];
    }

    public function sendMail(ViewEvent $event): void
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$user instanceof User || Request::METHOD_POST !== $method) {
            return;
        }

        $baseUrl = $this->params->get('app.baseurl');

        $html = '<p>Bonjour ' . $user->getFirstname() . ',</p>';
        $html .= '<p>Veuillez cliquer sur ce lien pour configurer un nouveau mot de passe :<br>';
        $html .= '<a href="' . $baseUrl . '/reset-password?token='. $user->getResetToken() .'" target="_blank">Créer un nouveau mot de passe</a></p>';
        $html .= '<p>Vous disposez de 30min, puis le lien expirera. Il faudra alors aller sur la page <a href="' . $baseUrl . '/forgot-password">Mot de passe oublié</a> pour obtenir un nouveau lien.</p>';
        $html .= '<p>Bien cordialement,<br>';
        $html .= 'L\'équipe Groupe EMC</p>';

        $message = (new Email())
            ->to($user->getEmail())
            ->subject('Configurer votre mot de passe - Groupe EMC')
            ->html($html);

        $this->mailer->send($message);
    }
}