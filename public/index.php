<?php
require '../vendor/autoload.php' ;
session_start();
date_default_timezone_set('America/Santiago');

switch($_SERVER['SERVER_NAME']){
    case "localhost":
        $env = "development";
    break;

    case "instadminer.herokuapp.com":
        $env = "staging";
    break;

    default:
        $env = "production";
    break;
}
//$app->response->headers['Access-Control-Allow-Origin']="*";

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim(array(
    'mode'=> $env
));

require 'functions.php';

$JSON = function() use ($app){
    return function() use ($app){
        $app->response->headers['Content-Type']="text/json";
    };
};

$authenticateForRole = function ( $role = 'member' ) {
    return function () use ( $role ) {
        /*$user = User::fetchFromDatabaseSomehow();
        if ( $user->belongsToRole($role) === false ) {
            $app = \Slim\Slim::getInstance();
            $app->flash('error', 'Login required');
            $app->redirect('/login');
        }*/
    };
};





$app->get('/',function() use ($app){
    $app->render('login.html');
});

$app->get('/admin(/:page)',function($page=1) use ($app){

    $last_four = getData(1,4);
    $all_data = getData($page,10);


    $instagram  = R::count('instagram');
    $totalPages = ceil($instagram/10);
    $paginator = array('totalPages'=>$totalPages);

    $app->render('admin/index.php',array(
        'last_four'=>$last_four,
        'all_data'=>$all_data,
        'paginator' => $paginator)
    );
})->name('paginator')->conditions(array('page' => '\d+'));


$app->get('/admin/add',function() use ($app){
    $app->render('add.php');
});


$app->get('/admin/get-data-instagram',$JSON,function() use ($app,$instagram){
        $search = $app->request->get('search');
        $data = getDataInstagram($instagram,$search);
        echo json_encode($data);
});

$app->get('/admin/more-data-instagram',$JSON,function() use ($app,$instagram){
    $content = file_get_contents($app->request->get('url'));
    echo $content;
});

$app->post('/admin/add-data',$JSON,function() use ($app,$instagram){
        $id = $app->request->post('id');
        $data = getDataInstagramById($instagram,$id);
        echo json_encode($data);
});
$app->post('/admin/delete-data',$JSON,function() use ($app){
    $id = $app->request->post('id');
    $data = deleteData($id);
    echo json_encode(true);
});

$app->post('/admin/show-data',$JSON,function() use ($app){
    $id = $app->request->post('id');
    $data = showData($id);
    echo json_encode(true);
});

$app->post('/admin/hide-data',$JSON,function() use ($app){
    $id = $app->request->post('id');
    $data = hideData($id);
    echo json_encode(true);
});


$app->get('/admin/delete',function() use ($app){
    $app->render('index.php');
});

$app->get('/admin/hide',function() use ($app){
    $app->render('index.php');
});


$app->get('/admin/export',function() use ($app){
    $app->render('index.php');
});






$app->error(function (\Exception $e) use ($app) {
    fail($e->getMessage(),0,500);
});

function fail($message = '', $status = 0, $http_status = 400){
    global $app;
    $response = new StdClass();
    $response->message = $message;
    $response->status = $status;
    $app->halt($http_status, json_encode($response));
};

$app->run();
