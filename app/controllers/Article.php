<?php

class Article
{
	// FIXME: Mark liked article as unread; UNIQUE constraint.
	public static function read($f3)
	{
		$db = $f3->get("DB");
		$post = $f3->get("POST");

		if($post["do"] == "Mark as read") {
			$db->exec("DELETE FROM unread WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		} else {
			$db->exec("INSERT INTO unread (user_id, article_id) VALUES (:user_id, :article_id);", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		}
	}

	public static function like($f3)
	{
		$db = $f3->get("DB");
		$post = $f3->get("POST");

		if($post["do"] == "Like") {
			// TODO: Single transaction.
			$db->exec("DELETE FROM unread WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
			$db->exec("INSERT INTO liked (user_id, article_id) VALUES (:user_id, :article_id);", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		} else {
			$db->exec("DELETE FROM liked WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		}
	}
}
