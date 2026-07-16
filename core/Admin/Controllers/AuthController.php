<?php

namespace Core\Admin\Controllers;

use Core\Auth;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Url;
use Core\View;

final class AuthController
{
    public function showLogin(array $params): void
    {
        if (Auth::check()) {
            Response::redirect(Url::admin(''));
        }

        echo View::render(CORE_PATH . '/Admin/Views/auth/login.php', [
            'error' => null,
        ]);
    }

    public function login(array $params): void
    {
        Csrf::verifyRequestOrFail();

        $identifier = Request::trimmed('identifier');
        $password = (string) Request::post('password', '');

        $result = Auth::attempt($identifier, $password);

        if (!$result['ok']) {
            echo View::render(CORE_PATH . '/Admin/Views/auth/login.php', [
                'error' => $result['message'],
                'identifier' => $identifier,
            ]);
            return;
        }

        Response::redirect(Url::admin(''));
    }

    public function logout(array $params): void
    {
        Auth::logout();
        Response::redirect(Url::admin('login'));
    }
}
