<?php

$f3 = require("../framework/base.php");
$f3->config("../config.ini");

$db = new DB\SQL("sqlite:../database/nymphaea.sqlite");
$db->exec("PRAGMA foreign_keys = ON;");

$f3->route("GET @index: /", function($f3) use($db) {
	$result = $db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content FROM articles JOIN liked ON id = liked.article_id GROUP BY liked.article_id ORDER BY COUNT(liked.article_id) DESC;");
	$f3->set("articles", $result);

	echo Template::instance()->render("landing.html");
});

$f3->route("POST /sign-in", function($f3) use($db) {
	$post = $f3->get("POST");

	$result = $db->exec("SELECT id, real_name, email, password FROM users where email = :email;", [":email" => $post["email"]]);

	if(count($result) == 0) {
		$f3->reroute("@index");
	}

	$user = $result[0];

	if(password_verify($post["password"], $user["password"]) == false) {
		$f3->reroute("@index");
	}

	// TODO: copyTo().
	$f3->set("SESSION.user", ["id" => $user["id"], "real_name" => $user["real_name"], "email" => $user["email"]]);
	$f3->reroute("@home");
});

$f3->route("GET /sign-out", function($f3) {
	$f3->set("SESSION.user", null);
	$f3->reroute("@index");
});

$f3->route("POST /register", function($f3) use($db) {
	$post = $f3->get("POST");

	if($post["password"] != $post["password_confirmation"]) {
		$f3->reroute("@index");
	}

	$result = $db->exec("INSERT INTO users (real_name, email, password) VALUES(:real_name, :email, :password);", [":real_name" => $post["real_name"], ":email" => $post["email"], ":password" => password_hash($post["password"], PASSWORD_DEFAULT)]);

	if($result == true) {
		$f3->reroute("@home");
	} else {
		$f3->reroute("@index");
	}
});

// TODO: beforeRoute().
$f3->route("GET @home: /home", function($f3) use($db) {
	$result = $db->exec("SELECT COUNT(article_id) AS unread FROM unread WHERE user_id = :user_id;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$f3->set("unread_count", $result[0]["unread"]);

	// FIXME: Unread 0.
	$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND (folder IS NULL OR folder = '') ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	for($i = 0; $i < count($result); $i++) {
		$result[$i]["icon"] = base64_encode($result[$i]["icon"]);
	}
	$f3->set("nullSubscriptions", $result);

	$result = $db->exec("SELECT DISTINCT folder FROM subscriptions WHERE user_id = :user_id AND folder IS NOT NULL AND folder <> '' ORDER BY folder ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$folders = $result;

	for($i = 0; $i < count($folders); $i++) {
		// FIXME: Unread 0.
		$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND folder = :folder ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"], ":folder" => $folders[$i]["folder"]]);
		for($j = 0; $j < count($result); $j++) {
			$result[$j]["icon"] = base64_encode($result[$j]["icon"]);
		}
		$folders[$i]["subscriptions"] = $result;
	}

	$f3->set("folders", $folders);

	$result = $db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content FROM articles JOIN liked ON id = liked.article_id GROUP BY liked.article_id ORDER BY COUNT(liked.article_id) DESC;");
	$f3->set("featured", $result);

	$f3->set("header", "home_header.html");
	$f3->set("content", "home.html");

	echo Template::instance()->render("layout.html");
});

// TODO: beforeRoute().
$f3->route("GET @feed: /home/@feed_id", function($f3, $params) use($db) {
	// TODO: Error checking.
	$result = $db->exec("SELECT COUNT(article_id) AS unread FROM unread WHERE user_id = :user_id;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$f3->set("unread_count", $result[0]["unread"]);

	$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND (folder IS NULL OR folder = '') ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	for($i = 0; $i < count($result); $i++) {
		$result[$i]["icon"] = base64_encode($result[$i]["icon"]);
	}
	$f3->set("nullSubscriptions", $result);

	$result = $db->exec("SELECT DISTINCT folder FROM subscriptions WHERE user_id = :user_id AND folder IS NOT NULL AND folder <> '' ORDER BY folder ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$folders = $result;

	for($i = 0; $i < count($folders); $i++) {
		// FIXME: Unread 0.
		$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND folder = :folder ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"], ":folder" => $folders[$i]["folder"]]);
		for($j = 0; $j < count($result); $j++) {
			$result[$j]["icon"] = base64_encode($result[$j]["icon"]);
		}
		$folders[$i]["subscriptions"] = $result;
	}

	$f3->set("folders", $folders);

	if(is_numeric($params["feed_id"]) == true && $params["feed_id"] > 0) {
		$result = $db->exec("SELECT id, name FROM feeds WHERE id = :feed_id;", [":feed_id" => $params["feed_id"]]);
		$f3->set("feedName", $result[0]["name"]);
		// TODO: Remove.
		$f3->set("SESSION.feed_id", $result[0]["id"]);

		$result = $db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, 1 AS unread, 0 AS liked FROM articles JOIN feeds ON feeds.id = articles.feed_id JOIN unread ON articles.id = unread.article_id JOIN users ON users.id = unread.user_id WHERE articles.feed_id = :feed_id AND users.id = :user_id ORDER BY articles.date DESC;", [":feed_id" => $params["feed_id"], ":user_id" => $f3->get("SESSION.user")["id"]]);
	} else if($params["feed_id"] == "unread") {
		$f3->set("feedName", "Unread articles");
		$result = $db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, unread.user_id AS unread, liked.user_id AS liked FROM articles JOIN unread ON articles.id = unread.article_id JOIN users ON users.id = unread.user_id LEFT JOIN liked ON articles.id = liked.article_id WHERE users.id = :user_id ORDER BY articles.date DESC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	} else if($params["feed_id"] == "liked") {
		$f3->set("feedName", "Liked articles");
		$result = $db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, 0 AS unread, liked.user_id AS liked FROM articles JOIN liked ON articles.id = liked.article_id JOIN users ON users.id = liked.user_id WHERE users.id = :user_id ORDER BY articles.date DESC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	} else if($params["feed_id"] == "all") {
		$f3->set("feedName", "All articles");
		$result = $db->exec("SELECT articles.id, articles.title, articles.url, articles.author, articles.date, articles.content, unread.user_id AS unread, liked.user_id AS liked FROM subscriptions JOIN feeds ON subscriptions.feed_id = feeds.id JOIN articles ON feeds.id = articles.feed_id LEFT JOIN liked ON articles.id = liked.article_id LEFT JOIN unread ON articles.id = unread.article_id WHERE subscriptions.user_id = :user_id ORDER BY articles.date DESC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	}

	$f3->set("articles", $result);

	$f3->set("header", "feed_header.html");
	$f3->set("content", "feed.html");

	echo Template::instance()->render("layout.html");
});

// TODO: beforeRoute().
// TODO: class="active".
$f3->route("GET /settings", function($f3) use($db) {
	// TODO: Error checking.
	$result = $db->exec("SELECT COUNT(article_id) AS unread FROM unread WHERE user_id = :user_id;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$f3->set("unread_count", $result[0]["unread"]);

	$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND (folder IS NULL OR folder = '') ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$f3->set("nullSubscriptions", $result);

	$result = $db->exec("SELECT DISTINCT folder FROM subscriptions WHERE user_id = :user_id AND folder IS NOT NULL AND folder <> '' ORDER BY folder ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$folders = $result;

	for($i = 0; $i < count($folders); $i++) {
		// FIXME: Unread 0.
		$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND folder = :folder ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"], ":folder" => $folders[$i]["folder"]]);
		$folders[$i]["subscriptions"] = $result;
	}

	$f3->set("folders", $folders);

	$f3->set("header", "settings_header.html");
	$f3->set("content", "settings.html");

	echo Template::instance()->render("layout.html");
});

// TODO: beforeRoute().
// TODO: class="active".
$f3->route("GET /help", function($f3) use($db) {
	// TODO: Error checking.
	$result = $db->exec("SELECT COUNT(article_id) AS unread FROM unread WHERE user_id = :user_id;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$f3->set("unread_count", $result[0]["unread"]);

	$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND (folder IS NULL OR folder = '') ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$f3->set("nullSubscriptions", $result);

	$result = $db->exec("SELECT DISTINCT folder FROM subscriptions WHERE user_id = :user_id AND folder IS NOT NULL AND folder <> '' ORDER BY folder ASC;", [":user_id" => $f3->get("SESSION.user")["id"]]);
	$folders = $result;

	for($i = 0; $i < count($folders); $i++) {
		// FIXME: Unread 0.
		$result = $db->exec("SELECT feeds.name, feed_id, feeds.icon, 0 AS unread FROM subscriptions JOIN feeds ON feed_id = feeds.id WHERE user_id = :user_id AND folder = :folder ORDER BY feeds.name ASC;", [":user_id" => $f3->get("SESSION.user")["id"], ":folder" => $folders[$i]["folder"]]);
		$folders[$i]["subscriptions"] = $result;
	}

	$f3->set("folders", $folders);

	$f3->set("header", "help_header.html");
	$f3->set("content", "help.html");

	echo Template::instance()->render("layout.html");
});

// TODO: Use named routes.
$f3->route("POST /read", function($f3) use($db) {
	$post = $f3->get("POST");

	if($post["do"] == "Mark as read") {
		$db->exec("DELETE FROM unread WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
	} else {
		$db->exec("INSERT INTO unread (user_id, article_id) VALUES (:user_id, :article_id);", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
	}
});

// TODO: Use named routes.
$f3->route("POST /like", function($f3) use($db) {
	$post = $f3->get("POST");

	if($post["do"] == "Like") {
		// TODO: Single transaction.
		$db->exec("DELETE FROM unread WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
		$db->exec("INSERT INTO liked (user_id, article_id) VALUES (:user_id, :article_id);", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
	} else {
		$db->exec("DELETE FROM liked WHERE user_id = :user_id AND article_id = :article_id;", [":user_id" => $f3->get("SESSION.user")["id"], ":article_id" => $post["id"]]);
	}
});

$f3->route("POST /subscribe", function($f3) use($db) {
	$url = $f3->get("POST")["url"];
	$xml = simplexml_load_file($url);

	$url = parse_url($url);
	$url = $url["scheme"] . "://" . $url["host"];
	$icon = file_get_contents("http://www.google.com/s2/favicons?domain=" . $url);

	$name = $xml->channel->title;
	$articles = isset($xml->item) ? $xml->item : $xml->channel->item;

	$feed = new DB\SQL\Mapper($db, "feeds");
	$feed->name = $name;
	$feed->icon = $icon;
	$feed->save();

	$article = new DB\SQL\Mapper($db, "articles");
	$unread = new DB\SQL\Mapper($db, "unread");

	$user = $f3->get("SESSION.user");

	foreach($articles as $temp)
	{
		$dc = $temp->children("http://purl.org/dc/elements/1.1/");

		if(isset($temp->author) == true) {
			$author = $temp->author;
		} else if(isset($dc->creator) == true) {
			$author = $dc->creator;
		} else {
			$author = NULL;
		}

		if(isset($temp->pubDate) == true) {
			$date = $temp->pubDate;
		} else if(isset($dc->date) == true) {
			$date = $dc->date;
		} else {
			$date = NULL;
		}

		$date = date("Y-m-d H:i:s", strtotime($date));

		$article->reset();
		$article->feed_id = $feed->_id;
		$article->title = $temp->title;
		$article->url = $temp->link;
		$article->author = $author;
		$article->date = $date;
		$article->content = $temp->description;
		$article->save();

		$unread->reset();
		$unread->user_id = $user["id"];
		$unread->article_id = $article->_id;
		$unread->save();
	}

	$db->exec("INSERT INTO subscriptions (user_id, feed_id) VALUES (:user_id, :feed_id);", [":user_id" => $user["id"], ":feed_id" => $feed->_id]);

	$f3->reroute("@feed(@feed_id = " . $feed->_id . ")");
});

// FIXME.
$f3->route("POST /unsubscribe", function($f3) use($db) {
	// FIXME: feed_id.
	$db->exec("DELETE FROM feeds WHERE id = :id;", [":id" => $f3->get("SESSION.feed_id")]);
	$f3->reroute("@home");
});

$f3->run();
