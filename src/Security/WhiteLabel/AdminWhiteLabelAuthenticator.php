<?php

namespace App\Security\WhiteLabel;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AdminWhiteLabelAuthenticator extends AbstractLoginFormAuthenticator 
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'wl_admin_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UserPasswordHasherInterface $passwordEncoder
    ) {}

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $session = $request->getSession();
        if (!$session->has('_username')) {
            $session->set('_username', '');
        }
        $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Espace Admin</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&display=swap" rel="stylesheet">
            <style>
                body {
                    background-color: #121212;
                    color: #f1f1f1;
                    font-family: 'Outfit', sans-serif;
                }
                .login-container {
                    max-width: 400px;
                    margin: 100px auto;
                    padding: 30px;
                    background-color: #1e1e1e;
                    border-radius: 8px;
                    box-shadow: 0 4px 30px rgba(0,0,0,0.5);
                }
                label {
                    color: #ddd;
                }
                .form-control {
                    background-color: #2c2c2c;
                    border: 1px solid #444;
                    color: #f1f1f1;
                }
                .form-control::placeholder {
                    color: #999;
                }
                .btn-primary {
                    background-color: #007bff;
                    border-color: #007bff;
                }
                .btn-primary:hover {
                    background-color: #0056b3;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2 class="mb-4 text-center">Espace Admin</h2>
                <form method="POST" action="/wl-admin/login">
                    <input type="hidden" name="_csrf_token" value="{$csrfToken}">
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="text" class="form-control" id="email" name="admin_login_form[email]" required placeholder="votre@email.com">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="admin_login_form[password]" required placeholder="••••••••">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
        </body>
        </html>
        HTML;

        return new Response($html);
    }

    public function supports(Request $request): bool
    {
        return rtrim($request->getPathInfo(), '/') === '/wl-admin/login' && $request->getMethod() === 'POST';
    }

    public function authenticate(Request $request): Passport
    {
        $formData = $request->request->all('admin_login_form');
        $email = isset($formData['email']) ? trim($formData['email']) : '';
        $password = $formData['password'] ?? '';

        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('Email requis.');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        $request->getSession()->set('_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_white_label_client1_admin'));
    }
}
