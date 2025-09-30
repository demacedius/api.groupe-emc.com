<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

class ResetPasswordController
{
    private $userPasswordEncoder;
    
    public function __construct(private ManagerRegistry $doctrine, UserPasswordEncoderInterface $userPasswordEncoder) {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function __invoke(Request $request) {
        $em = $this->doctrine->getManager();
        $content = json_decode($request->getContent(), true);
        
        if (!$content || !is_array($content) || !array_key_exists('token', $content) || !array_key_exists('password', $content)) {
            throw new BadRequestHttpException();
        }

        $token = $content['token'];
        $password = $content['password'];

        if (!$token || $token == '') {
            throw new BadRequestHttpException('Lien utilisé invalide');
        }

        $user = $em->getRepository(User::class)->findOneByResetToken($token);

        if (!$user) {
            throw new BadRequestHttpException('Lien utilisé invalide');
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Accès refusé");
        }

        $now = new \DateTime();
        if ($user->getResetExp()->getTimestamp() < $now->getTimestamp()) {
            throw new BadRequestHttpException('Lien utilisé expiré');
        }

        if (!$password || $password == '') {
            throw new BadRequestHttpException('Mot de passe manquant');
        }
        
        $user->setPassword(
            $this->userPasswordEncoder->encodePassword($user, $password)
        );
        $user->setResetToken(null);
        $em->persist($user);
        $em->flush();

        return new Response(
            'Nouveau mot de passe configuré',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }
}