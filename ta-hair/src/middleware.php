<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$authMiddleWare = function ($request, $response, $next) {
    $route = $request->getAttribute('route');
    $userId = $_SESSION['userId'] ?? false;
    if (empty($route)) {
        throw new NotFoundException($request, $response);
    }
    $name = $route->getName();
    $url = $request->getUri()->getPath();
    if (preg_match('/api/i', $url)) {
        return $next($request, $response);
    }
    if (preg_match('/payment/i', $url)) {
        return $next($request, $response);
    }
    if (in_array($name, ['get-login','post-login'])) {
    	if ($userId) {
    		return $response->withRedirect('/dashboard');
    	}
    	return $next($request, $response);
    }
    if (!$userId) {
    	return $response->withRedirect('/login');
    }
    return $next($request, $response);
};

$app->add($authMiddleWare);
