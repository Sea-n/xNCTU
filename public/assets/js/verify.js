var stopCountdown = 0;
var cd_time = 0;

function init() {
	if (document.getElementById('send-verify')) {
		document.getElementById('stuid').oninput = formUpdate;
		document.getElementById('send-verify').onsubmit = submitForm;
	}
}

function formUpdate() {
	var stuid = document.getElementById('stuid').value;
	var mailStuid = document.getElementById('mail-stuid');
	var mailYear = document.getElementById('mail-year');
	var mailUrl = document.getElementById('mail-url');
	var submit = document.getElementById('submit');

	if (cd_time == 0 && stuid.length == 9)
		submit.classList.remove('disabled');

	if (stuid == '')
		stuid = "108062000";
	stuid += "?????????";
	stuid = stuid.substr(0, 9);

	var year = stuid.substr(0, 3);
	var url = 'https://m' + year + '-mail.nthu.edu.tw/';

	mailStuid.innerText = stuid;
	mailYear.innerText = year;
	mailUrl.href = url;
	mailUrl.innerText = url;
}

function submitForm(e) {
	e.preventDefault();
	var stuid = document.getElementById('stuid').value;
	var submit = document.getElementById('submit');

	if (stuid.length != 9) {
		alert('學號格式錯誤！');
		return;
	}

	submit.classList.add('disabled');
	submit.value = "寄送中..."

	fetch('/api/verify', {
		method: 'POST',
		body: JSON.stringify({
			stuid: stuid,
		}),
		headers: {'content-type': 'application/json'}
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		if (!resp.ok) {
			alert(resp.msg);
			return;
		}

		cd_time = 30;
		stopCountdown = setInterval(() => {
			document.getElementById('submit').value = "重新寄送"
			if (cd_time == 0) {
				document.getElementById('submit').classList.remove('disabled');
				clearInterval(stopCountdown);
			} else {
				document.getElementById('submit').value += "（" + cd_time + "）"
				cd_time--;
			}
		}, 1000);
	});
}

function confirmVerify() {
	var urlParams = new URLSearchParams(window.location.search);
	var stuid = urlParams.get('stuid');
	var sub = urlParams.get('sub');
	var code = urlParams.get('code');

	fetch('/api/verify', {
		method: 'PATCH',
		body: JSON.stringify({
			stuid: stuid,
			sub: sub,
			code: code,
		}),
		headers: {'content-type': 'application/json'}
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		alert(resp.msg);
		if (resp.ok)
			location.href = '.';
	});
}

window.addEventListener("load", init);
