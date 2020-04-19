require("dotenv").config();
const { readFileSync } = require('fs');
const { promisify } = require('util');
const { createConnection } = require('mysql');
const { strictEqual, isAbove } = require('assert');
const { IgApiClient } = require('instagram-private-api');

(async () => {
	const id = process.argv[2];

	const conn = createConnection({
		host: 'localhost',
		user: 'xnctu',
		password: process.env.MYSQL_PASSWORD,
		database: 'xnctu',
	});
	const query = promisify(conn.query).bind(conn);

	conn.connect();
	const rows = await query('SELECT * FROM posts WHERE id = "' + id + '"');

	const post = rows[0];
	strictEqual(post.has_img, 1);
	strictEqual(post.instagram_id, '');
	const img = './img/' + post.uid + '.jpg';
	const msg = '#靠交' + post.id + '\n\n' + post.body;

	const ig = new IgApiClient();
	ig.state.generateDevice(process.env.IG_USERNAME);

	const auth = await ig.account.login(process.env.IG_USERNAME, process.env.IG_PASSWORD);

	const publishResult = await ig.publish.photo({
		file: await readFileSync(img),
		caption: msg,
	});

	console.log(publishResult);
	strictEqual(publishResult.status, 'ok');

	const code = publishResult.media.code;
	await query('UPDATE posts SET instagram_id = "' + code + '" WHERE uid = "' + post.uid + '"');
	conn.end();
})().catch((err) => {
	console.error(err);
	process.exit(1);
});
