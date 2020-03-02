function getPosts(limit, offset) {
	fetch('/api/posts?limit=' + limit + '&offset=' + offset)
	.then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
	});
}
