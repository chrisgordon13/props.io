<?php
class UserModel 
{	
	private $deps;
	public $id, $email, $password, $lists, $authenticated, $errors;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;

		$this->id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
		$this->email = isset($_SESSION['email']) ? $_SESSION['email'] : NULL;
		$this->password = isset($_SESSION['password']) ? $_SESSION['password'] : NULL;
		$this->authenticated = isset($_SESSION['authenticated']) ? $_SESSION['authenticated'] : false;		

		$this->errors = array();
	}

	public function create($user_data) 
	{		
		$user_created = false;

		$email = $user_data['email'];
		$password = $user_data['password'];
		$validation_error = false;

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$validation_error = true;
			$this->errors['email'] = "Please enter a valid email address.";
		}

		if (!filter_var($password, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[\s\S]{6,20}$/")))) {
			$validation_error = true;
			$this->errors['password'] = "Please enter a valid password having between 6 and 20 characters.";
		} 

		if (!$validation_error) {
			
			$db = $this->deps['db'];

			$c_pass = crypt($password, $this->deps['pass_salt']);

			$sql = "INSERT INTO users (email, password, date_added, date_updated) VALUES (:email, :password, NOW(), NOW())";

			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':email', $email, PDO::PARAM_STR);
			$stmt->bindValue(':password', $c_pass, PDO::PARAM_STR);

			try {

				$stmt->execute();
				$this->authenticated = true;
				$this->read($email);

				$user_created = true;;
			} catch (Exception $e) {

				$this->errors['email'] = "An account using this email address has already been created.";
			}
		}

		return $user_created;
	}

	public function read($email) 
	{		
		$user_found = false;

		$db = $this->deps['db'];

		$sql = "SELECT id, password FROM users WHERE email = :email";

		$stmt = $db->prepare($sql);
			
		$stmt->bindValue(':email', $email, PDO::PARAM_STR);

		if ($stmt->execute()) {

			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			
				$user_found = true;

				$_SESSION['user_id'] = $this->id = $row['id'];
				$_SESSION['email'] = $this->email = $email;
				$_SESSION['password'] = $this->password = $row['password'];
				$_SESSION['authenticated'] = $this->authenticated;
			}
		}

		return $user_found;
	}

	public function authenticate($user_data) 
	{		
		$email = $user_data['email'];
		$password = $user_data['password'];
		$user_validated = false;

		if ($this->read($email)) {		
			
			if (crypt($password, $this->deps['pass_salt']) == $this->password) {
				
				$user_validated = true;
				$this->authenticated = true;
				$this->read($email);
			} else {

				$this->errors['password'] = "The password you entered does not work for this account.";
			}

		} else {

			$this->errors['email'] = "We weren't able to find an account using this email address.";
		}

		return $user_validated;
	}	
}
