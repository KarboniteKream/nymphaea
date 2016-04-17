<?php

class Pages
{
	private $db = null;

	public function __construct($f3)
	{
		$this->db = $f3->get("DB");
	}

	public function beforeRoute($f3)
	{
		if($f3->get("PATH") != "/" && $f3->get("SESSION.user") == null) {
			$f3->reroute("@landing");
		}
	}

	// TODO: class="active"
	public function landing($f3)
	{
		$result = $this->db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content FROM articles JOIN liked ON id = liked.article_id GROUP BY liked.article_id ORDER BY COUNT(liked.article_id) DESC;");
		$f3->set("articles", $result);

		echo Template::instance()->render("landing.html");
	}

	public function loadSidebar($f3)
	{
		$result = $this->db->exec("SELECT COUNT(article_id) AS unread FROM unread WHERE user_id = :user_id;", [":user_id" => $f3->get("SESSION.user")["id"]]);
		$f3->set("unread_count", $result[0]["unread"]);

		// FIXME: Unread 0.
		$result = $this->db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND (folder IS NULL OR folder = '') ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
		for($i = 0; $i < count($result); $i++) {
			$result[$i]["icon"] = base64_encode($result[$i]["icon"]);
		}
		$f3->set("null_feeds", $result);

		$result = $this->db->exec("SELECT DISTINCT folder FROM subscriptions WHERE user_id = :user_id AND folder IS NOT NULL AND folder <> '' ORDER BY folder ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
		$folders = $result;

		for($i = 0; $i < count($folders); $i++) {
			// FIXME: Unread 0.
			$result = $this->db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND folder = :folder ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"], ":folder" => $folders[$i]["folder"]]);
			for($j = 0; $j < count($result); $j++) {
				$result[$j]["icon"] = base64_encode($result[$j]["icon"]);
			}
			$folders[$i]["subscriptions"] = $result;
		}

		$f3->set("folders", $folders);
	}

	public function home($f3)
	{
		$this->loadSidebar($f3);

		$result = $this->db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content FROM articles JOIN liked ON id = liked.article_id GROUP BY liked.article_id ORDER BY COUNT(liked.article_id) DESC;");
		$f3->set("featured", $result);

		$f3->set("header", "home_header.html");
		$f3->set("content", "home.html");

		echo Template::instance()->render("layout.html");
	}

	public function feed($f3, $params)
	{
		$this->loadSidebar($f3);

		if(is_numeric($params["feed_id"]) == true && $params["feed_id"] > 0) {
			$result = $this->db->exec("SELECT id, name FROM feeds WHERE id = :feed_id;", [":feed_id" => $params["feed_id"]]);
			$f3->set("feedName", $result[0]["name"]);
			// TODO: Remove.
			$f3->set("SESSION.feed_id", $result[0]["id"]);
			$result = $this->db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, 1 AS unread, 0 AS liked FROM articles JOIN feeds ON feeds.id = articles.feed_id JOIN unread ON articles.id = unread.article_id JOIN users ON users.id = unread.user_id WHERE articles.feed_id = :feed_id AND users.id = :user_id ORDER BY articles.date DESC;", [":feed_id" => $params["feed_id"], ":user_id" => $f3->get("SESSION.user")["id"]]);
		} else if($params["feed_id"] == "unread") {
			$f3->set("feedName", "Unread articles");
			$result = $this->db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, unread.user_id AS unread, liked.user_id AS liked FROM articles JOIN unread ON articles.id = unread.article_id JOIN users ON users.id = unread.user_id LEFT JOIN liked ON articles.id = liked.article_id WHERE users.id = :user_id ORDER BY articles.date DESC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
		} else if($params["feed_id"] == "liked") {
			$f3->set("feedName", "Liked articles");
			$result = $this->db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, 0 AS unread, liked.user_id AS liked FROM articles JOIN liked ON articles.id = liked.article_id JOIN users ON users.id = liked.user_id WHERE users.id = :user_id ORDER BY articles.date DESC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
		} else if($params["feed_id"] == "all") {
			$f3->set("feedName", "All articles");
			$result = $this->db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, unread.user_id AS unread, liked.user_id AS liked FROM subscriptions JOIN feeds ON subscriptions.feed_id = feeds.id JOIN articles ON feeds.id = articles.feed_id LEFT JOIN liked ON articles.id = liked.article_id LEFT JOIN unread ON articles.id = unread.article_id WHERE subscriptions.user_id = :user_id ORDER BY articles.date DESC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
		}

		$f3->set("articles", $result);

		$f3->set("header", "feed_header.html");
		$f3->set("content", "feed.html");

		echo Template::instance()->render("layout.html");
	}

	public function settings($f3)
	{
		$this->loadSidebar($f3);

		$f3->set("header", "settings_header.html");
		$f3->set("content", "settings.html");

		echo Template::instance()->render("layout.html");
	}

	public function help($f3)
	{
		$this->loadSidebar($f3);

		$f3->set("header", "help_header.html");
		$f3->set("content", "help.html");

		echo Template::instance()->render("layout.html");
	}
}
