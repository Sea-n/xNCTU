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
    const urlParams = new URLSearchParams(window.location.search);
    const button = document.getElementById('more');
    const offset = parseInt(button.dataset.offset);
    const params = {};

    if (offset < 0)
        return;

    if (button.classList.contains('disabled'))
        return;
    button.classList.add('disabled');

    let likes = urlParams.get('likes');
    let media = urlParams.get('media');
    let keyword = urlParams.get('keyword');
    if (likes) params['likes'] = likes;
    if (media) parmas['media'] = media;
    if (keyword) params['keyword'] = keyword;

    let limit = 50;
    if (offset >= 200)
        limit = offset;

    params['offset'] = offset;
    params['limit'] = limit;

    button.dataset.offset = offset + limit;
    getPosts(likes, params);
    setTimeout(() => {
        if (button.dataset.offset >= 0)
            button.classList.remove('disabled');
    }, 1000);
}

function getPosts(likes, params) {
    let url = '/api/posts?' + Object.keys(params)
        .map(k => k + '=' + encodeURIComponent(params[k])).join('&');
    fetch(url)
        .then(resp => resp.json())
        .then((resp) => {
            if (resp.length < params['limit']) {
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

    // TODO: use mp4 for GIF and Video
    if (item.media != 0)
        post.querySelector('#img').src = '/img/' + item.uid + '.jpg';

    let body = item.body;
    const blocks = body.split('\n');
    if (blocks.length > 15) {
        body = blocks.slice(0, 10).join('\n');
        read_more = true;
    }

    post.querySelector('#body').innerHTML = toHTML(body);

    if (read_more)
        post.querySelector('#body').innerHTML += '<br><br><a href="/post/' + item.id + '">閱讀全文</a></p>';

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
