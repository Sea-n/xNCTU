function approve(uid) {
	if (!confirm('您確定要通過此貼文嗎？'))
		return;

	vote(uid, 1, '通過附註');
}

function reject(uid) {
	if (!confirm('您確定要駁回此貼文嗎？'))
		return;

	vote(uid, -1, '駁回理由');
}

function vote(uid, type, reason_prompt) {
	var login = document.querySelector('nav .right a[data-type="login"]');
	if (login) {
		alert('請先登入');
		login.click();
		return;
	}

	var reason = prompt('請輸入' + reason_prompt + ' (5 - 100 字)');
	if (reason === null)
		return;

	if (reason.length < 5) {
		alert('請輸入 5 個字以上');
		return;
	}

	if (reason.length > 100) {
		alert('請輸入 100 個字以內');
		return;
	}


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
			card.querySelector('#approvals').innerText = resp.approvals;
			card.querySelector('#rejects').innerText = resp.rejects;

			document.querySelector(".attached button.positive").classList.add("disabled");
			document.querySelector(".attached button.negative").classList.add("disabled");
		} else
			alert("Error: " + resp.msg);
	});
}
