<?php

class User
{
	private $db = null;

	public function __construct($f3)
	{
		$this->db = $f3->get("DB");
	}

	public function register($f3)
	{
		$post = $f3->get("POST");

		if($post["password"] != $post["password_confirmation"]) {
			$f3->reroute("@landing");
		}

		$result = $this->db->exec("INSERT INTO users (real_name, email, password) VALUES(:real_name, :email, :password);", [":real_name" => $post["real_name"], ":email" => $post["email"], ":password" => password_hash($post["password"], PASSWORD_DEFAULT)]);

		if($result == true) {
			$f3->reroute("@home");
		} else {
			$f3->reroute("@landing");
		}
	}

	public function signIn($f3)
	{
		$post = $f3->get("POST");

		$result = $this->db->exec("SELECT id, real_name, email, password FROM users where email = :email;", [":email" => $post["email"]]);

		if(count($result) == 0) {
			$f3->reroute("@landing");
		}

		$user = $result[0];

		if(password_verify($post["password"], $user["password"]) == false) {
			$f3->reroute("@landing");
		}

		// TODO: copyTo().
		$f3->set("SESSION.user", ["id" => $user["id"], "real_name" => $user["real_name"], "email" => $user["email"]]);
		$f3->reroute("@home");
	}

	public function signOut($f3)
	{
		$f3->set("SESSION.user", null);
		$f3->clear("SESSION");
		$f3->reroute("@landing");
	}
}
