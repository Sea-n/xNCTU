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

		updateVotes(uid);
	});
}

function updateVotes(uid) {
	var table = document.getElementById('votes');
	var tbody = table.tBodies[0];

	fetch('/api/votes?uid=' + uid)
	.then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		if (resp.ok) {
			card.querySelector('#approvals').innerText = resp.approvals;
			card.querySelector('#rejects').innerText = resp.rejects;

			tbody.innerHTML = '';
			var votes = resp.votes;
			for (var i=0; i<votes.length; i++) {
				var vote = votes[i];
				appendRow(vote.vote, vote.dep, vote.name, vote.reason);
			}
		} else
			alert(resp.msg);
	});
}

function appendRow(vote, dep, name, reason) {
	var table = document.getElementById('votes');
	var tbody = table.tBodies[0];

	var lastNum = 0;
	var lastRow = tbody.lastElementChild;
	if (lastRow)
		lastNum = lastRow.firstElementChild.innerText;
	var no = parseInt(lastNum) + 1;

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
	tr.cells[4].appendChild(document.createTextNode(reason));

	tbody.appendChild(tr);
}
