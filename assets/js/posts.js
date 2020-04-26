setInterval(() => {
	var button = document.getElementById('more');
	if (button.getBoundingClientRect().top > 2000)
		return;

	if (button.classList.contains('disabled'))
		return;

	more();
}, 500);

document.addEventListener('keyup', (e) => {
	if (e.key == 'Escape') {
		ts('#modal').modal('hide');
	}
});

function more() {
	var button = document.getElementById('more');
	var offset = parseInt(button.dataset.offset);

	if (offset < 0)
		return;

	if (button.classList.contains('disabled'))
		return;
	button.classList.add('disabled');

	var limit = 50;
	if (offset >= 200)
		limit = offset;

	button.dataset.offset = offset + limit;
	getPosts(limit, offset);
	setTimeout(() => {
		if (button.dataset.offset >= 0)
			button.classList.remove('disabled');
	}, 1000);
}

function getPosts(limit, offset) {
	fetch('/api/posts?limit=' + limit + '&offset=' + offset)
	.then(resp => resp.json())
	.then((resp) => {
		if (resp.length < limit) {
			var button = document.getElementById('more');
			button.classList.add('disabled');
			button.innerText = '已無更多文章';
			button.dataset.offset = -87;
		}
		resp.forEach((item) => {
			appendPost(item);
		})
	});
}

function appendPost(item) {
	var posts = document.getElementById('posts');
	var template = document.getElementById('post-template');
	var post = document.createElement('div');
	post.append(template.content.cloneNode(true));

	post.id = 'post-' + item.id;
	post.querySelector('#hashtag').innerText = '#靠交' + item.id;
	post.querySelector('#hashtag').href = '/post/' + item.id;

	if (item.has_img) {
		post.querySelector('#img').onclick = showImg;
		post.querySelector('#img').src = '/img/' + item.uid + '.jpg';
	}

	post.querySelector('#body').innerHTML = toHTML(item.body);

	post.querySelector('#author-name').innerText = item.author_name;
	post.querySelector('#author-photo').src = item.author_photo;
	if (item.ip_masked)
		post.querySelector('#ip-inner').innerText = item.ip_masked;
	else
		post.querySelector('#ip-outter').innerHTML = '';

	post.querySelector('#approvals').innerText = item.approvals;
	post.querySelector('#rejects').innerText = item.rejects;

	post.querySelector('#time').dataset.ts = item.time;

	posts.appendChild(post);
}
