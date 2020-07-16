<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/DbOperations.php';


$app = AppFactory::create();
$app->setBasePath("/onlineShoppingApi/public/index.php");

$app->post('/createuser', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('email', 'password', 'username'), $request, $response)){

        $request_data = $request->getParsedBody(); 

        $email = $request_data['email'];
        $password = $request_data['password'];
        $username = $request_data['username'];
        

        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations; 

        $result = $db->createUser($email, $hash_password, $username);
        
        if($result == USER_CREATED){

            $message = array(); 
            $message['error'] = false; 
            $message['message'] = 'User created successfully';

            $body = $response->getBody();
			$body->write(json_encode($message));

            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(201);

        }else if($result == USER_FAILURE){

            $message = array(); 
            $message['error'] = true; 
            $message['message'] = 'Some error occurred';

            $body = $response->getBody();
			$body->write(json_encode($message));

            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(422);    

        }else if($result == USER_EXISTS){
            $message = array(); 
            $message['error'] = true; 
            $message['message'] = 'User Already Exists';

            $body = $response->getBody();
			$body->write(json_encode($message));


            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(422);    
        }
    }
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);    
});
$app->post('/userlogin', function(Request $request, Response $response){
	if (!haveEmptyParameters(array('email','password'), $request, $response)) {
		 $request_data = $request->getParsedBody(); 

        $email = $request_data['email'];
        $password = $request_data['password'];

        $db = new DbOperations; 
        $result = $db->userLogin($email, $password);

        if ($result == USER_AUTHENTICATED) {
        	$user = $db->getUserdByEmail($email);
        	$response_data = array();

        	$response_data['error'] = false;
        	$response_data['message'] = 'Login successfully';
        	$response_data['user'] = $user;

        	$body = $response->getBody();
			$body->write(json_encode($response_data));

			return $response
        					->withHeader('Content-type', 'application/json')
        					->withStatus(200);
        	
        }elseif ($result == USER_NOT_FOUND) {
        	$response_data = array();

        	$response_data['error'] = true;
        	$response_data['message'] = 'User dose not exist';

        	$body = $response->getBody();
			$body->write(json_encode($response_data));

			return $response
        					->withHeader('Content-type', 'application/json')
        					->withStatus(200);
        }elseif ($result == USER_PASSWORD_DO_NOT_MATCH) {
        	$response_data = array();

        	$response_data['error'] = true;
        	$response_data['message'] = 'Invalid credential';

        	$body = $response->getBody();
			$body->write(json_encode($response_data));

			return $response
        					->withHeader('Content-type', 'application/json')
        					->withStatus(200);
        }

	}
	return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});

$app->get('/allusers', function(Request $request, Response $response){
	$db = new DbOperations; 
    $users = $db->getAllUsers();

    $response_data = array();
    $response_data['error'] = false;
    $response_data['users'] = $users;

    $body = $response->getBody();
	$body->write(json_encode($response_data));

	return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);

});
$app->post('/updateuser/{id}', function(Request $request, Response $response, array $args){

    $id = $args['id'];
     

    if(!haveEmptyParameters(array('email','username'), $request, $response)){

        $request_data = $request->getParsedBody(); 
        $email = $request_data['email'];
        $username = $request_data['username'];

        $db = new DbOperations; 

        if($db->updateUser($email, $username, $id)){
            $response_data = array(); 
            $response_data['error'] = false; 
            $response_data['message'] = 'User Updated Successfully';
            $user = $db->getUserdByEmail($email);
            $response_data['user'] = $user; 

            $body = $response->getBody();
			$body->write(json_encode($response_data));

            return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);  
        
        }else{
            $response_data = array(); 
            $response_data['error'] = true; 
            $response_data['message'] = 'Please try again later';
            $user = $db->getUserdByEmail($email);
            $response_data['user'] = $user; 

            $body = $response->getBody();
			$body->write(json_encode($response_data));

            return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);  
              
        }

    }
    
    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);  

});
$app->post('/updatepassword', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('currentpassword', 'newpassword', 'email'), $request, $response)){
        
        $request_data = $request->getParsedBody(); 

        $currentpassword = $request_data['currentpassword'];
        $newpassword = $request_data['newpassword'];
        $email = $request_data['email']; 

        $db = new DbOperations; 

        $result = $db->updatePassword($currentpassword, $newpassword, $email);

        if($result == PASSWORD_CHANGED){
            $response_data = array(); 
            $response_data['error'] = false;
            $response_data['message'] = 'Password Changed';
            $body = $response->getBody();
			$body->write(json_encode($response_data));

            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);

        }else if($result == PASSWORD_DO_NOT_MATCH){
            $response_data = array(); 
            $response_data['error'] = true;
            $response_data['message'] = 'You have given wrong password';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);
        }else if($result == PASSWORD_NOT_CHANGED){
            $response_data = array(); 
            $response_data['error'] = true;
            $response_data['message'] = 'Some error occurred';
            $body = $response->getBody();
			$body->write(json_encode($response_data));

            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);  
});
$app->delete('/deleteuser/{id}', function(Request $request, Response $response, array $args){
	$id = $args['id'];

	$db = new DbOperations;
	$response_data = array();
	if($db->deleteUser($id)){
		$response_data['error'] = false;
		$response_data['message'] = "Delete user successfully";
	
	} else{
		$response_data['error'] = true;
		$response_data['message'] = "Some error occurred Please try again later";
	}
	
	$body = $response->getBody();
	$body->write(json_encode($response_data));

	return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422); 
});

function haveEmptyParameters($required_params, $request, $response){
    $error = false; 
    $error_params = '';
    $request_params = $request->getParsedBody(); 

    foreach($required_params as $param){
        if(!isset($request_params[$param]) || strlen($request_params[$param])<=0){
            $error = true; 
            $error_params .= $param . ', ';
        }
    }

    if($error){
        $error_detail = array();
        $error_detail['error'] = true; 
        $error_detail['message'] = 'Required parameters ' . substr($error_params, 0, -2) . ' are missing or empty';
        $body = $response->getBody();
		$body->write(json_encode($error_detail));

    }
    return $error; 
}

$app->run();