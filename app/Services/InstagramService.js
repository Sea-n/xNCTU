require("dotenv").config({path: __dirname + '../../.env'});
const {readFileSync, writeFileSync, existsSync} = require('fs');
const {promisify} = require('util');
const {createConnection} = require('mysql');
const {strictEqual} = require('assert');
const {IgApiClient} = require('instagram-private-api');

(async () => {
    const id = process.argv[2];

    const conn = createConnection({
        host: process.env.DB_HOST,
        user: process.env.DB_USERNAME,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_DATABASE,
        charset: 'utf8mb4',
    });
    const query = promisify(conn.query).bind(conn);

    conn.connect();
    const rows = await query('SELECT * FROM posts WHERE id = "' + id + '"');

    const post = rows[0];
    strictEqual(post.media, 1);
    strictEqual(post.instagram_id, '');
    const img = __dirname + '/../../public/img/' + post.uid + '.jpg';
    const msg = post.body + '\n\n#' + process.env.HASHTAG + post.id + ' #靠北交大';

    /* Instagram */
    const ig = new IgApiClient();
    ig.state.generateDevice(process.env.INSTAGRAM_USERNAME);

    const sessionFile = __dirname + '/../../storage/app/instagram-' + process.env.INSTAGRAM_USERNAME + '.session';
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

    await ig.account.login(process.env.INSTAGRAM_USERNAME, process.env.INSTAGRAM_PASSWORD);

    const publishResult = await ig.publish.photo({
        file: await readFileSync(img),
        caption: msg,
    });

    console.log(publishResult);
    strictEqual(publishResult.status, 'ok');

    const code = publishResult.media.code;
    await query('UPDATE posts SET instagram_id = "' + code + '" WHERE id = "' + id + '"');
    conn.end();
})().catch((err) => {
    console.error(err);
    process.exit(1);
});
