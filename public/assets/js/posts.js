setInterval(() => {
    const button = document.getElementById('more');
    if (button.getBoundingClientRect().top > 2000)
        return;

    if (button.classList.contains('disabled'))
        return;

    more();
}, 500);

document.addEventListener('keyup', (e) => {
    if (e.key === 'Escape') {
        ts('#modal').modal('hide');
    }
});

function more() {
    const button = document.getElementById('more');
    const offset = parseInt(button.dataset.offset);

    if (offset < 0)
        return;

    const urlParams = new URLSearchParams(window.location.search);
    let likes = urlParams.get('likes');
    if (!likes) likes = '';
    let media = urlParams.get('media');
    if (!media) media = '';

    if (button.classList.contains('disabled'))
        return;
    button.classList.add('disabled');

    let limit = 50;
    if (offset >= 200)
        limit = offset;

    button.dataset.offset = offset + limit;
    getPosts(likes, media, limit, offset);
    setTimeout(() => {
        if (button.dataset.offset >= 0)
            button.classList.remove('disabled');
    }, 1000);
}

function getPosts(likes, media, limit, offset) {
    fetch(`/api/posts?likes=${likes}&media=${media}&limit=${limit}&offset=${offset}`)
        .then(resp => resp.json())
        .then((resp) => {
            if (resp.length < limit) {
                const button = document.getElementById('more');
                button.classList.add('disabled');
                button.innerText = '已無更多文章';
                button.dataset.offset = "-87";
            }
            resp.forEach((item) => {
                appendPost(item);
            })
        });
}

function appendPost(item) {
    let read_more = false;
    const posts = document.getElementById('posts');
    const template = document.getElementById('post-template');
    const post = document.createElement('div');
    post.append(template.content.cloneNode(true));

    post.id = 'post-' + item.id;
    post.querySelector('#hashtag').innerText = '#靠交' + item.id;
    post.querySelector('#hashtag').href = '/post/' + item.id;

    if (item.media != 0)
        post.querySelector('#img').onclick = showImg;

    if (item.media == 1)
        post.querySelector('#img').src = '/img/' + item.uid + '.jpg';

    if (item.media == 2)
        post.querySelector('#img').src = '/img/' + item.uid + '.gif';

    if (item.media == 3)
        post.querySelector('#img').src = '/img/' + item.uid + '.mp4';

    let body = item.body;
    const block = body.split('\n.\n.\n.\n.\n.\n.\n.\n.\n.\n.\n');
    if (block.length > 1) {
        body = block[0];
        read_more = true;
    }

    post.querySelector('#body').innerHTML = toHTML(body);

    if (read_more)
        post.querySelector('#body').innerHTML += '<p>.<br>.<br>.<br>.<br>.<br>. . . . . <a href="/post/' + item.id + '">閱讀全文</a></p>';

    post.querySelector('#author-name').innerText = item.author_name;
    post.querySelector('#author-photo').src = item.author_photo;
    if (item.ip_masked)
        post.querySelector('#ip-inner').innerText = item.ip_masked;
    else
        post.querySelector('#ip-outer').innerHTML = '';

    post.querySelector('#approvals').innerText = item.approvals;
    post.querySelector('#rejects').innerText = item.rejects;

    post.querySelector('#time').dataset.ts = item.time;

    posts.appendChild(post);
}
