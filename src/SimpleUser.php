<?php
// to access this class the file needs to be included and it will need to be called by
// \MineSQL\SimpleUser(array('host' => ect.));
namespace MineSQL;

// The class name.
class SimpleUser
{

	// These variables cannot be accessed outside of this class, we are doing
	// this because we do not want anything outside of this application to be
	// able to access the database details. 
	// we do not need to define each variable on its own line in a properties declaration.
	// as long as all the variables are in the same scope (ie private or public) they can 
	// share the same line and are seperated with commas.
	private $dbHost, $dbName, $dbUser, $dbPass, $pdo;

	// this variable can be accessed outside of the class simply by $SimpleUser->registerError; 
	// I'm doing this so you can easily access a registration error and show it to the user simply without
	// error codes.
	public $registerError;

	// this enables you to use SimpleUser::SINGLE_SESSION on the login() method to 
	// only have a user login for one request. 
	const SINGLE_SESSION = 1;


	// in order to use this function it must be initialized with database information.
	// this function will also check and make sure it has the compatibilities it needs in order to work.
	// when you actually initialize this class, ($log = \MineSQL\SimpleUser($databaseinfo)) this function will be called.
	public function __construct(array $database)
	{

		try 
		{
			$this->pdo = new \PDO('mysql:host='.$database['host'].';dbname='.$database['name'], $database['user'], $database['password']);

		} catch(PDOException $e) {

			throw new Exception("There was an issue logging into the mysql database: ".$e, 1);
		}

		// we are checking to ensure that we have the bcrypt function on the server, the easiest way to check this is to check if the password_hash function exists.
		if(!function_exists('password_hash'))
		{
			// the function does not exist so we will throw an error since this class requires bcrypt.
			throw new Exception("This class requires the password_compat functions in order to work.", 1);
			
		}
	}

	// this checks if a user is already logged in via a session.
	public function check()
	{

		if(isset($_SESSION['user']) && !empty($_SESSION['user']['username']))
		{
			return true;
		}

		return false;

	}

	public function logout()
	{
		if($this->checkSession())
		{
			unset($_SESSION['user']);
			return true;
		}

		throw new Exception("You cannot log out of a session-less request.", 1);
		
	}


	// you will use this function to check if a user is logged in or not.
	public function login($username, $password, $singleSession = 0)
	{

		$user = $this->findUserByName($username);

		//Check if the user exists
		if(!$user)
		{
			return false;
		}

		// check if the password matches
		if(!password_verify($password, $user['password']))
		{
			return false;
		}

		// iniitalize the session if it is not a single session and return true
		if($singleSession==0)
		{
			if($this->checkSession())
			{
				// we will make sure that the session user does not obtain the password hash
				unset($user['password']);

				$_SESSION['user'] = $user;
				return true;
			}

			throw new Exception("You need to start a php session in order to use a session based login.", 1);
			

		}

		//return true if it is only a single session
		return true;


	}


	public function register(array $userInfo)
	{
		// lets set local values for these three variables since we will be doing checks on them
		$username = $userInfo['username'];
		$password = $userInfo['password'];
		$cpassword = $userInfo['confirm_password'];

		//requires username and password and password confirm.
		if(empty($username) || empty($password) || empty($cpassword))
		{	
			$this->registerError = 'The required username, password, and confirm password fields are not fully filled.';
			return false;
		}

		// this ensures the username is alphanumerical (no spaces, weird characters)
		if(!ctype_alnum($username))
		{	
			$this->registerError = 'The username is not alphanumerical.';
			return false;
		}

		// checks and makes sure the password and confirmed password is the same
		if($password!=$cpassword)
		{
			$this->registerError = 'The passwords do not match.';
			return false;
		}

		// ensures the password length is at least 6 characters
		if(strlen($password)<6)
		{
			$this->registerError = 'The password needs to be at least 6 characters long';
			return false;
		}

		// please note, we do all the quicker tasks (if statements) before we continue with a database interaction.
		// this increases the execution speed of the code if a user gives invalid information, and in a high traffic website
		// this sort of thing is fairly important.

		// this checks the database with the supplied username and checks to see if its already in use
		if($this->findUserByName($userInfo['username']))
		{
			$this->registerError = 'The username already exists.';
			return false;
		}

		//remove the confirm_password from the data to be inserted
		unset($userInfo['confirm_password']);
		//encrypt the password with bcrypt
		$userInfo['password'] = password_hash($userInfo['password'], PASSWORD_BCRYPT);

		// insert all of the userInfo into the database dynamically 
		// be careful and ensure all the values and keys to be inserted already have columns in the database, otherwise it will throw an error.
		if($this->insertIntoUsers($userInfo))
		{
			return true;
		} 

		$this->registerError = 'There was an issue with inserting the user.';
		return false;



	}

	//checks the php session to ensure no bugs in the login function
	// this function will not be accessible outside of this class
	private function checkSession()
	{
		// checks if the session is started, works for every php version
		if (version_compare(phpversion(), '5.4.0', '<')) {
		     if(session_id() == '') {
		        return false;
		     }
		 }
		 else {
		    if (session_status() == PHP_SESSION_NONE) {
		        return false;
		    }
		 }

		 return true;

	}

	// utilizes the pdo property (which holds the php pdo object) to obtain a database row by a username
	private function findUserByName($username)
	{
		$initq = $this->pdo->prepare("SELECT * FROM users where username=:username LIMIT 1");
		$initq->execute(array(':username' => $username));

		// PDO either returns an array (on a successful row found) or a 0/false value (for a non-result found)
		return $initq->fetch();
	}

	// this inserts any data in the information array with the key being the column name and the key value as the data to be inserted
	private function insertIntoUsers(array $information)
	{
		// http://php.net/manual/en/function.implode.php#51416 
		// please note, the key value of the information is supposed to be the column name - this should
		// be hard coded and is a security vulnerability if used as a dynamic value or user input
		$SQL = 'INSERT INTO users ('.implode(", ", array_keys($information)).') VALUES (:'.implode(", :", array_keys($information)).')';

		$toInsert = array();

		foreach($information as $key=>$value)
		{
			$toInsert[':'.$key] = $value;
		}

		$instq = $this->pdo->prepare($SQL);

		try {

			if($instq->execute($toInsert))
			{
				return true;
			}
	
			return false;

		} catch(Exception $e) {

			throw new Exception("There was an issue with inserting this user.".$e, 1);
			return false;
			
		}
	}




}