--
-- File generated with SQLiteStudio v3.0.7 on Sat Apr 16 14:58:03 2016
--
-- Text encoding used: UTF-8
--
PRAGMA foreign_keys = off;
BEGIN TRANSACTION;

-- Table: users
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id        INTEGER       PRIMARY KEY AUTOINCREMENT,
    real_name VARCHAR (128) NOT NULL,
    email     VARCHAR (128) NOT NULL
                            UNIQUE,
    password  VARCHAR (256) NOT NULL
);


-- Table: feeds
DROP TABLE IF EXISTS feeds;

CREATE TABLE feeds (
    id   INTEGER       PRIMARY KEY AUTOINCREMENT,
    name VARCHAR (128) NOT NULL,
    icon BLOB          NOT NULL
);


-- Table: liked
DROP TABLE IF EXISTS liked;

CREATE TABLE liked (
    user_id    INTEGER REFERENCES users (id) ON DELETE CASCADE
                                             ON UPDATE CASCADE
                       NOT NULL,
    article_id INTEGER REFERENCES articles (id) ON DELETE CASCADE
                                                ON UPDATE CASCADE
                       NOT NULL,
    PRIMARY KEY (
        user_id,
        article_id
    )
);


-- Table: unread
DROP TABLE IF EXISTS unread;

CREATE TABLE unread (
    user_id    INTEGER REFERENCES users (id) ON DELETE CASCADE
                                             ON UPDATE CASCADE
                       NOT NULL,
    article_id INTEGER REFERENCES articles (id) ON DELETE CASCADE
                                                ON UPDATE CASCADE
                       NOT NULL,
    PRIMARY KEY (
        user_id,
        article_id
    )
);


-- Table: articles
DROP TABLE IF EXISTS articles;

CREATE TABLE articles (
    id      INTEGER        PRIMARY KEY AUTOINCREMENT,
    feed_id INTEGER        REFERENCES feeds (id) ON DELETE CASCADE
                                                 ON UPDATE CASCADE
                           NOT NULL,
    title   VARCHAR (256)  NOT NULL,
    url     VARCHAR (1024),
    author  VARCHAR (128),
    date    DATETIME       NOT NULL,
    content TEXT
);


-- Table: subscriptions
DROP TABLE IF EXISTS subscriptions;

CREATE TABLE subscriptions (
    user_id INTEGER      REFERENCES users (id) ON DELETE CASCADE
                                               ON UPDATE CASCADE
                         NOT NULL,
    feed_id INTEGER      REFERENCES feeds (id) ON DELETE CASCADE
                                               ON UPDATE CASCADE
                         NOT NULL,
    folder  VARCHAR (64),
    PRIMARY KEY (
        user_id,
        feed_id
    )
);


COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
