// google sign up
   $routes->get('google-login', 'AuthController::googleLogin'); // Google sign-in link
   $routes->get('google-callback', 'AuthController::googleCallback'); // Google callback URL
