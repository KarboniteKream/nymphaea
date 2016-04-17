<?php

class Article
{
	private $db = null;

	public function __construct($f3)
	{
		$this->db = $f3->get("DB");
	}

	// FIXME: Mark liked article as unread; UNIQUE constraint.
	public function read($f3)
	{
		$post = $f3->get("POST");

		if($post["do"] == "Mark as read") {
			$this->db->exec("DELETE FROM unread WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		} else {
			$this->db->exec("INSERT INTO unread (user_id, article_id) VALUES (:user_id, :article_id);", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		}
	}

	public function like($f3)
	{
		$post = $f3->get("POST");

		if($post["do"] == "Like") {
			// TODO: Single transaction.
			$this->db->exec("DELETE FROM unread WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
			$this->db->exec("INSERT INTO liked (user_id, article_id) VALUES (:user_id, :article_id);", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		} else {
			$this->db->exec("DELETE FROM liked WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		}
	}
}
