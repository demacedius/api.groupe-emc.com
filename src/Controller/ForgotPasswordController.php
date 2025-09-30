<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\User;

class ForgotPasswordController extends AbstractController
{
    private $mailer;

    public function __construct(private ManagerRegistry $doctrine, MailerInterface $mailer) {
        $this->mailer = $mailer;
    }

    public function __invoke(Request $request) {
        $em = $this->doctrine->getManager();
        $content = json_decode($request->getContent(), true);
        
        if (!$content || !is_array($content) || !array_key_exists('email', $content)) {
            throw new BadRequestHttpException();
        }

        $email = $content['email'];

        if (!$email || $email == '') {
            throw new BadRequestHttpException('Email invalide');
        }

        $user = $em->getRepository(User::class)->findOneByEmail($email);

        if (!$user) {
            throw new BadRequestHttpException('Email invalide');
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Accès refusé");
        }

        $expDate = new \DateTime();
        $add = new \DateInterval('PT30M');
        $expDate->add($add);
        $user->setResetExp($expDate);
        
        $token = bin2hex(random_bytes(20));
        $user->setResetToken($token);
        $em->persist($user);
        $em->flush();

        $baseUrl = $this->getParameter('app.baseurl');

        $html = '<p>Bonjour ' . $user->getFirstname() . ',</p>';
        $html .= '<p>Veuillez cliquer sur ce lien pour réinitialiser votre mot de passe :<br>';
        $html .= '<a href="' . $baseUrl . '/reset-password?token='. $user->getResetToken() .'" target="_blank">Créer un nouveau mot de passe</a></p>';
        $html .= '<p>Vous disposez de 30min, puis le lien expirera.</p>';
        $html .= '<p>Bien cordialement,<br>';
        $html .= 'L\'équipe Groupe EMC</p>';

        $message = (new Email())
            ->to($user->getEmail())
            ->subject('Configurer votre mot de passe - Groupe EMC')
            ->html($html);

        $this->mailer->send($message);

        return new Response(
            'Un lien de réinitialisation vous a été envoyé par email',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }
}