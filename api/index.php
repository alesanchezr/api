<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Api\Src\Handlers;

use Chadicus\Slim\OAuth2\Routes;
use Chadicus\Slim\OAuth2\Middleware;
use Slim\Http;
use Slim\Views;
use OAuth2\Storage;
use OAuth2\GrantType;

require '../vendor/autoload.php';
require 'dependencies.php';

/**
 * OAuth 2.0 implementation
 * Using externarl library: https://github.com/chadicus/slim-oauth2
 * And PHP Oauth Server: https://bshaffer.github.io/oauth2-server-php-docs/
 **/
$storage = new OAuth2\Storage\Pdo(array('dsn' => 'mysql:host=localhost;dbname=c9', 'username' => 'alesanchezr', 'password' => ''));
$server = new OAuth2\Server($storage,array(
    'access_lifetime' => 86400
));

//Enable Authorization Code credentials to allow request from authorization code.
$server->addGrantType(new GrantType\AuthorizationCode($storage));
//Enable ClientCredentials to allo clients to generate an authorization code.
$server->addGrantType(new GrantType\ClientCredentials($storage));
//Enable user login form
$server->addGrantType(new GrantType\UserCredentials($storage));

$getUsernameMiddleware = function ($request, $response, $next) {
    
    $body = $request->getParsedBody();

    $response = $next($request, $response);//do the next middleware layer action

    $session = new \Custom\Middleware\SessionHelper;
    if(isset($body['username'])) 
    {
        $user = User::where('username', $body['username'])->first();
        if(!$user) throw new Exception('There is now user corresponding to these credentials in the platform.');
    
        $session->set('user_id', $user->id);
        $session->set('user_name', $body['username']);
    }
    else $session->destroy();

    return $response;
};

$sessionMiddleware = new \Custom\Middleware\Session;([
  'name' => 'dummy_session',
  'autorefresh' => true,
  'lifetime' => 3600
]);

//The HTML views for the OAuth Autentication process
$renderer = new Views\PhpRenderer( __DIR__ . '/vendor/chadicus/slim-oauth2-routes/templates');
$app->map(['GET', 'POST'], Routes\Authorize::ROUTE, new Routes\Authorize($server, $renderer))->setName('authorize');
$app->post(Routes\Token::ROUTE, new Routes\Token($server))->setName('token')->add($sessionMiddleware)->add($getUsernameMiddleware);
$app->map(['GET', 'POST'], Routes\ReceiveCode::ROUTE, new Routes\ReceiveCode($renderer))->setName('receive-code');
//Creating the Middleware to intercept all request and ask for authorization before continuing
$authorization = new Middleware\Authorization($server, $app->getContainer());


/**
 * Everything Related to the user
 **/
$userHandler = new UserHandler($app);
$app->get('/me', array($userHandler, 'getMe'))->add($sessionMiddleware)->add($authorization);



/**
 * Everything Related to the locations
 **/
$locationHandler = new LocationHandler($app);
$app->get('/locations/', array($locationHandler, 'getAllHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/location/{location_id}', array($locationHandler, 'getSingleHandler'))->add($authorization->withRequiredScope(['admin']));

$app->post('/location/', array($locationHandler, 'createLocationHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/location/{location_id}', array($locationHandler, 'updateLocationHandler'))->add($authorization->withRequiredScope(['admin']));
$app->delete('/location/{location_id}', array($locationHandler, 'deleteLocationHandler'))->add($authorization->withRequiredScope(['admin']));




/**
 * Everything Related to the cohorts
 **/
$cohortHandler = new CohortHandler($app);
//$app->get('/cohorts/', array($cohortHandler, 'getAllHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/cohorts/location/{location_id}', array($cohortHandler, 'getAllCohortsFromLocationHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/cohort/{cohort_id}', array($cohortHandler, 'getSingleHandler'))->add($authorization->withRequiredScope(['admin']));

$app->post('/student/cohort/{cohort_id}', array($cohortHandler, 'addStudentToCohortHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/students/cohort/{cohort_id}', array($cohortHandler, 'getCohortStudentsHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/cohort/', array($cohortHandler, 'createCohortHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/cohort/{cohort_id}', array($cohortHandler, 'updateCohortHandler'))->add($authorization->withRequiredScope(['admin']));
$app->delete('/cohort/{cohort_id}', array($cohortHandler, 'deleteCohortHandler'))->add($authorization->withRequiredScope(['admin']));







/**
 * Everything Related to the student itself
 **/
$studentHandler = new StudentHandler($app);
$app->get('/students/', array($studentHandler, 'getAllHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/student/{student_id}', array($studentHandler, 'getStudentHandler'))->add($authorization->withRequiredScope(['admin']));

$app->post('/student/', array($studentHandler, 'createStudentHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/student/{student_id}', array($studentHandler, 'updateStudentHandler'))->add($authorization->withRequiredScope(['admin']));
$app->delete('/student/{student_id}', array($studentHandler, 'deleteStudentHandler'))->add($authorization->withRequiredScope(['admin']));










/**
 * Everything Related to the student badges
 **/
$badgeHandler = new BadgeHandler($app);
$app->get('/badges/', array($badgeHandler, 'getAllHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/badge/{badge_id}', array($badgeHandler, 'getSingleHandler'))->add($authorization->withRequiredScope(['admin']));

$app->get('/badges/student/{student_id}', array($badgeHandler, 'getAllStudentBadgesHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/badge/', array($badgeHandler, 'createOrUpdateBadgeHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/badge/{badge_id}', array($badgeHandler, 'createOrUpdateBadgeHandler'))->add($authorization->withRequiredScope(['admin']));
$app->delete('/badge/{badge_id}', array($badgeHandler, 'deleteBadgeHandler'))->add($authorization->withRequiredScope(['admin']));








/**
 * Everything Related to the student activities
 **/
$app->post('/activity/student/{student_id}', array($studentHandler, 'createStudentActivityHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/activity/student/{student_id}', array($studentHandler, 'getStudentActivityHandler'))->add($authorization->withRequiredScope(['admin']));
$app->delete('/activity/{activity_id}', array($studentHandler, 'deleteStudentActivityHandler'))->add($authorization->withRequiredScope(['admin']));








/**
 * Everything Related to the student specialties
 **/
$specialtyHandler = new SpecialtyHandler($app);
$app->get('/specialty/{specialty_id}', array($specialtyHandler, 'getSingleHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/specialties/', array($specialtyHandler, 'getAllHandler'))->add($authorization->withRequiredScope(['admin']));

$app->get('/specialties/profile/{profile_id}', array($specialtyHandler, 'getProfileSpecialtiesHandler'))->add($authorization->withRequiredScope(['admin']));
$app->get('/specialties/student/{student_id}', array($specialtyHandler, 'getStudentSpecialtiesHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/specialty/{specialty_id}', array($specialtyHandler, 'updateSpecialtyHandler'))->add($authorization->withRequiredScope(['admin']));
$app->post('/specialty/', array($specialtyHandler, 'createSpecialtyHandler'))->add($authorization->withRequiredScope(['admin']));
$app->delete('/specialty/{specialty_id}', array($studentHandler, 'deleteSpecialtyHandler'))->add($authorization->withRequiredScope(['admin']));



/**
 * Runing the app alfter all configuration
 **/
$app->run();
