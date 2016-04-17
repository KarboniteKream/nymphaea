<?php

$f3 = require("lib/base.php");

$f3->config("app/config/globals.ini");
$f3->config("app/config/routes.ini");

$db = new DB\SQL("sqlite:app/database/nymphaea.sqlite");
$db->exec("PRAGMA foreign_keys = ON;");
$f3->set("DB", $db);

$f3->run();
