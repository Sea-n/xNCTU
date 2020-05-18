SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

/*
 * submission status codes:
 *
 * 0 pending submit
 * 1 confirmed submit
 * 2 sening review message
 * 3 sent all review
 * 4 selected to post
 * 5 posted to all SNS
 *
 * 10 demo post
 *
 * -1 deleted for unknown reason
 * -2 rejected
 * -3 deleted by author (hidden)
 * -4 deleted by admin
 *
 * -11 deleted and hidden by admin
 * -12 rate limited
 * -13 unconfirmed timeout
 */

DROP TABLE IF EXISTS posts;
CREATE TABLE posts (
	uid CHAR(4) PRIMARY KEY UNIQUE,
	id INTEGER UNIQUE,
	body VARCHAR(8700) NOT NULL,
	has_img BOOLEAN DEFAULT FALSE NOT NULL,
	ip_addr VARCHAR(87) NOT NULL,
	author_id VARCHAR(87) DEFAULT '' NOT NULL,
	author_name VARCHAR(87) DEFAULT '' NOT NULL,
	author_photo VARCHAR(2048) DEFAULT '' NOT NULL,
	approvals INTEGER DEFAULT 0 NOT NULL,
	rejects INTEGER DEFAULT 0 NOT NULL,
	telegram_id INTEGER DEFAULT 0 NOT NULL,
	plurk_id INTEGER DEFAULT 0 NOT NULL,
	twitter_id BIGINT DEFAULT 0 NOT NULL,
	facebook_id BIGINT DEFAULT 0 NOT NULL,
	instagram_id VARCHAR(20) DEFAULT '' NOT NULL,
	status INTEGER DEFAULT 0 NOT NULL,
	posted_at TIMESTAMP NULL,
	deleted_at TIMESTAMP NULL,
	delete_note VARCHAR(870) DEFAULT '' NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

DROP TABLE IF EXISTS users;
CREATE TABLE users (
	name VARCHAR(87) DEFAULT '' NOT NULL,
	stuid VARCHAR(87) PRIMARY KEY NOT NULL,
	mail VARCHAR(320) DEFAULT '' NOT NULL,
	tg_id INTEGER UNIQUE,
	tg_name VARCHAR(870),
	tg_username VARCHAR(87),
	tg_photo VARCHAR(2048),
	approvals INTEGER DEFAULT 0 NOT NULL,
	rejects INTEGER DEFAULT 0 NOT NULL,
	last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

DROP TABLE IF EXISTS votes;
CREATE TABLE votes (
	uid CHAR(4) NOT NULL,
	voter VARCHAR(87) NOT NULL,
	vote INTEGER NOT NULL,
	reason VARCHAR(870) DEFAULT '' NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

DROP TABLE IF EXISTS tg_msg;
CREATE TABLE tg_msg (
	uid CHAR(4) NOT NULL,
	chat_id INTEGER NOT NULL,
	msg_id INTEGER NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

COMMIT;
