<?php 

	class DbOperations{

		private $con;

		function __construct(){
			require_once dirname(__FILE__) . '/DbConnect.php';
		

			$db = new DbConnect;

			$this->con = $db->connect();

		}

		public function createUser($email, $password, $username){
           if(!$this->isEmailExist($email)){
                $stmt = $this->con->prepare("INSERT INTO register_users (email, password, username) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $password, $username);
                if($stmt->execute()){
                    return USER_CREATED; 
                }else{
                    return USER_FAILURE;
                }
           }
           return USER_EXISTS; 
        }

        public function userLogin($email, $password){
        	if($this->isEmailExist($email)){
        		$hash_password = $this->getUsersPasswordByEmail($email);
        		if(password_verify($password, $hash_password)){
        			return USER_AUTHENTICATED;
        		}else{
        			return USER_PASSWORD_DO_NOT_MATCH;
        		}
        	}else{
        		return USER_NOT_FOUND;
        	}
        }

        private function getUsersPasswordByEmail($email){
        	$stmt = $this->con->prepare("SELECT password FROM register_users WHERE email = ?");
        	$stmt->bind_param("s", $email);
        	$stmt->execute();
        	$stmt->bind_result($password);
        	$stmt->fetch();
        	return $password;
        }

        public function getAllUsers(){
        	$stmt = $this->con->prepare("SELECT id, email, username FROM register_users;");
        	$stmt->execute();
        	$stmt->bind_result($id, $email, $username);
        	$users = array();
        	while($stmt->fetch()){
        		$user = array();
        		$user['id'] = $id;
        		$user['email'] = $email;
        		$user['username'] = $username;
        		array_push($users, $user);
       	 	}
       	 	return $users;
        }
        public function getUserdByEmail($email){
        	$stmt = $this->con->prepare("SELECT id, email, username FROM register_users WHERE email = ?");
        	$stmt->bind_param("s", $email);
        	$stmt->execute();
        	$stmt->bind_result($id, $email, $username);
        	$stmt->fetch();
        	$user = array();
        	$user['id'] = $id;
        	$user['email'] = $email;
        	$user['username'] = $username;
        	return $user;
         }

        public function updateUser($email, $username, $id){
            $stmt = $this->con->prepare("UPDATE register_users SET email = ?, username = ? WHERE id = ?");
            $stmt->bind_param("ssi", $email, $username, $id);
            if($stmt->execute())
                return true; 
            return false; 
        }
        public function updatePassword($currentpassword, $newpassword, $email){
            $hashed_password = $this->getUsersPasswordByEmail($email);
            
            if(password_verify($currentpassword, $hashed_password)){
                
                $hash_password = password_hash($newpassword, PASSWORD_DEFAULT);
                $stmt = $this->con->prepare("UPDATE register_users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss",$hash_password, $email);

                if($stmt->execute())
                    return PASSWORD_CHANGED;
                return PASSWORD_NOT_CHANGED;

            }else{
                return PASSWORD_DO_NOT_MATCH; 
            }
        }
        public function deleteUser($id){
        	$stmt = $this->con->prepare("DELETE FROM register_users WHERE id = ?");
        	$stmt->bind_param("i", $id);
        	if($stmt->execute()){
        		return true;
        	}else{
        		return false;
        	}
        }
		private function isEmailExist($email){
            $stmt = $this->con->prepare("SELECT id FROM register_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows > 0;  
        }
	}