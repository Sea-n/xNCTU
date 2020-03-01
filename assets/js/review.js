setInterval(updateVotes, 10*1000);

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
		uid: uid,
		vote: type,
		reason: reason
	};

	fetch('/api/vote', {
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

			updateVotes();
	});
}

function updateVotes() {
	var button = document.getElementById('refresh');
	button.classList.add('disabled');

	var uid = document.body.dataset.uid;
	if (!uid)
		return;

	fetch('/api/votes?uid=' + uid)
	.then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		if (resp.ok) {
			if (resp.id)
				location.href = '/post/' + resp.id;

			var card = document.getElementById('post-' + uid);
			card.querySelector('#approvals').innerText = resp.approvals;
			card.querySelector('#rejects').innerText = resp.rejects;

			updateVotesTable(resp.votes);
			setTimeout(() => {
				button.classList.remove('disabled');
			}, 800);
		} else
			alert(resp.msg);
	});
}

function updateVotesTable(votes) {
	var table = document.getElementById('votes');
	var tbody = table.tBodies[0];

	var newBody = document.createElement('tbody');
	for (var i=0; i<votes.length; i++) {
		var vote = votes[i];
		var tr = voteRow(i+1, vote.vote, vote.dep, vote.name, vote.reason_html);
		newBody.appendChild(tr);
	}

	tbody.innerHTML = newBody.innerHTML;
}

function voteRow(no, vote, dep, name, reason) {
	var type = '❓ 未知';
	if (vote == 1)
		type = '✅ 通過';
	if (vote == -1)
		type = '❌ 駁回';

	var tr = document.createElement('tr');
	for (var i = 0; i < 5; i++)
		tr.appendChild(document.createElement('td'));

	tr.cells[0].appendChild(document.createTextNode(no));
	tr.cells[1].appendChild(document.createTextNode(type));
	tr.cells[2].appendChild(document.createTextNode(dep));
	tr.cells[3].appendChild(document.createTextNode(name));
	tr.cells[4].innerHTML = reason;

	return tr;
}
