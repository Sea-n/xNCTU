const PATH = '/usr/share/nginx/x.nctu.app';

require("dotenv").config({ path: PATH + '/.env' });
const { readFileSync, writeFileSync, existsSync } = require('fs');
const { promisify } = require('util');
const { createConnection } = require('mysql');
const { strictEqual } = require('assert');
const { IgApiClient } = require('instagram-private-api');

(async () => {
	const id = process.argv[2];

	const conn = createConnection({
		host: 'localhost',
		user: process.env.MYSQL_USERNAME,
		password: process.env.MYSQL_PASSWORD,
		database: process.env.MYSQL_USERNAME,
		charset : 'utf8mb4',
	});
	const query = promisify(conn.query).bind(conn);

	conn.connect();
	const rows = await query('SELECT * FROM posts WHERE id = "' + id + '"');

	const post = rows[0];
	strictEqual(post.has_img, 1);
	strictEqual(post.instagram_id, '');
	const img = PATH + '/img/' + post.uid + '.jpg';
	const msg = post.body + '\n\n#靠交' + post.id + ' #靠北交大';

	/* Instagram */
	const ig = new IgApiClient();
	ig.state.generateDevice(process.env.IG_USERNAME);

	const sessionFile = '/temp/' + process.env.MYSQL_USERNAME + '-ig.session';
	ig.request.end$.subscribe(async () => {
		const serialized = await ig.state.serialize();
		delete serialized.constants;
		const json = JSON.stringify(serialized);
		writeFileSync(sessionFile, json);
	});

	if (existsSync(sessionFile)) {
		const session = readFileSync(sessionFile);
		const state = JSON.parse(session);
		await ig.state.deserialize(state);
	}

	await ig.account.login(process.env.IG_USERNAME, process.env.IG_PASSWORD);

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
