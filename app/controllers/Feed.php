<?php

class Feed
{
	private $db = null;

	public function __construct($f3)
	{
		$this->db = $f3->get("DB");
	}

	public function subscribe($f3)
	{
		$url = $f3->get("POST")["url"];
		$xml = simplexml_load_file($url);

		$url = preg_replace("/^http:/i", "https:", $url);
		$url = parse_url($url);
		$icon = file_get_contents("https://www.google.com/s2/favicons?domain=" . $url["scheme"] . "://" . $url["host"]);

		$name = $xml->channel->title;
		$articles = isset($xml->item) ? $xml->item : $xml->channel->item;

		$feed = new DB\SQL\Mapper($this->db, "feeds");
		$feed->name = $name;
		$feed->icon = $icon;
		$feed->save();

		$article = new DB\SQL\Mapper($this->db, "articles");
		$unread = new DB\SQL\Mapper($this->db, "unread");

		$user = $f3->get("SESSION.user");

		foreach($articles as $temp) {
			$dc = $temp->children("https://purl.org/dc/elements/1.1/");

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
			$article->url = preg_replace("/^http:/i", "https:", $temp->link);
			$article->author = $author;
			$article->date = $date;
			$article->content = preg_replace("/http:\/\//", "https://", $temp->description);
			$article->save();

			$unread->reset();
			$unread->user_id = $user["id"];
			$unread->article_id = $article->_id;
			$unread->save();
		}

		$this->db->exec("INSERT INTO subscriptions (user_id, feed_id) VALUES (:user_id, :feed_id);", [":user_id" => $user["id"], ":feed_id" => $feed->_id]);

		$f3->reroute("@feed(@feed_id = " . $feed->_id . ")");
	}

	public function unsubscribe($f3)
	{
		// FIXME: feed_id.
		$this->db->exec("DELETE FROM feeds WHERE id = :id;", [":id" => $f3->get("SESSION.feed_id")]);
		$f3->reroute("@home");
	}
}
