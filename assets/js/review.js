function approve(uid) {
	if (!confirm('您確定要通過此貼文嗎？'))
		return;

	vote(uid, 1, '');
}

function reject(uid) {
	if (!confirm('您確定要駁回此貼文嗎？'))
		return;

	var reason = prompt('請輸入駁回理由');
	if (reason.length < 5) {
		alert('請輸入 5 個字以上');
		return;
	}

	vote(uid, -1, reason);
}

function vote(uid, type, reason) {
	data = {
		action: 'vote',
		uid: uid,
		vote: type,
		reason: reason
	};

	fetch('/api', {
		method: 'POST',
		body: JSON.stringify(data),
		headers: {'content-type': 'application/json'}
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		var card = document.getElementById('post-' + uid);
		if (resp.ok) {
			card.querySelector('#approval').innerText = resp.approval;
			card.querySelector('#rejects').innerText = resp.rejects;
		} else
			alert("Error: " + resp.msg);
	});
}
