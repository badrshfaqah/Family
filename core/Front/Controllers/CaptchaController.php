<?php

namespace Core\Front\Controllers;

use Core\Support\Captcha;
use Core\Support\Request;

final class CaptchaController
{
    public function image(array $params): void
    {
        $a = Request::int('a', 0);
        $b = Request::int('b', 0);
        if (!$a || !$b || !Captcha::isImageSupported()) {
            http_response_code(404);
            return;
        }
        Captcha::renderImage($a, $b);
    }
}
